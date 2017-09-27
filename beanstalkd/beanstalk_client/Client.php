<?php
/**
 * beanstalk: A minimalistic PHP beanstalk client.
 *
 * Copyright (c) 2009-2015 David Persson
 *
 * Distributed under the terms of the MIT License.
 * Redistributions of files must retain the above copyright notice.
 */

namespace Beanstalk;

use RuntimeException;

/**
 * Beanstalk队列服务的接口。 实现beanstalk协议规范1.9。 在适当的情况下，
 * 协议中的文档已添加到此类的docblock中。
 *
 * @link https://github.com/kr/beanstalkd/blob/master/doc/protocol.txt
 * @link https://github.com/kr/beanstalkd/blob/master/doc/protocol.zh-CN.md (中文)
 */
class Client {

	/**
     * 可分配给作业的最小优先级值。 最低优先级值也是作业最高优先级。(这个没有用到，为什么会写呢？)
     *
	 * @var integer
	 */
	const MIN_PRIORITY = 0;

	/**
     * 可以分配给作业的最大优先级值。 最大优先级值也是作业可以具有的最低优先级。(这个也没有用到，为什么会写呢？)
	 *
	 * @var integer
	 */
	const MAX_PRIORITY = 4294967295;

	/**
     * 保留一个布尔值，指示当前是否建立与服务器的连接。
	 *
	 * @var boolean
	 */
	public $connected = false;

	/**
	 * 保存配置值。
	 * @var array
	 */
	protected $_config = [];

	/**
	 * 当前的连接资源句柄（如果有的话）。
	 *
	 * @var resource
	 */
	protected $_connection;

	/**
	 * Constructor.
	 *
	 * @param array $config An array of configuration values:
     *        - `'persistent'`  是否使长连接，默认为“true”，因为FAQ建议使用长连接。
	 *        - `'host'`        你要连接的beanstalk服务器主机名或IP地址，默认为127.0.0.1。
	 *        - `'port'`        要连接的服务器端口，默认为“11300”。
	 *        - `'timeout'`     建立连接时的超时秒数，默认为1。
	 *        - `'logger'`      支持PSR-3的日志实例
	 *
	 * @link https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = [
			'persistent' => true,
			'host' => '127.0.0.1',
			'port' => 11300,
			'timeout' => 1,
			'logger' => null
		];
		$this->_config = $config + $defaults;
	}

	/**
	 * Destructor, disconnects from the server.
	 *
	 * @return void
	 */
	public function __destruct() {
		$this->disconnect();
	}

	/**
     * 启动与Beanstalk服务器的套接字连接。 生成的流将不会有任何超时设置。
     * 这意味着它可以等待无限的时间，直到数据包可用。
     * 这是执行阻塞读取所必需的。
	 *
	 * @see \Beanstalk\Client::$_connection
	 * @see \Beanstalk\Client::reserve()
	 * @return boolean 如果连接建立，则为true，否则为false。
	 */
	public function connect() {
		if (isset($this->_connection)) {
			$this->disconnect();
		}

		$function = $this->_config['persistent'] ? 'pfsockopen' : 'fsockopen';
		$params = [$this->_config['host'], $this->_config['port'], &$errNum, &$errStr];

		if ($this->_config['timeout']) {
			$params[] = $this->_config['timeout'];
		}
		$this->_connection = @call_user_func_array($function, $params);

		if (!empty($errNum) || !empty($errStr)) {
			$this->_error("{$errNum}: {$errStr}");
		}

		$this->connected = is_resource($this->_connection);

		if ($this->connected) {
			stream_set_timeout($this->_connection, -1);
		}
		return $this->connected;
	}

	/**
     * 如果要退出，通过第一个信令关闭在beanstalk服务器的连接,
     * 然后才实际关闭套接字连接。
     *
	 * @return boolean 返回`true`时关闭成功
	 */
	public function disconnect() {
		if (!is_resource($this->_connection)) {
			$this->connected = false;
		} else {
			$this->_write('quit');
			$this->connected = !fclose($this->_connection);

			if (!$this->connected) {
				$this->_connection = null;
			}
		}
		return !$this->connected;
	}

	/**
	 * 向日志文件发送错误消息，如果配置了日志文件的话
     *
	 * @param string $message 错误信息
	 * @return void
	 */
	protected function _error($message) {
		if ($this->_config['logger']) {
			$this->_config['logger']->error($message);
		}
	}

	/**
	 * 将数据包写入套接字。 在写入套接字之前，将检查连接的可用性。
	 * @param string $data
	 * @return integer|boolean 成功时返回写入的字符长度(bytes)，如果是`false`为写入失败。
	 */
	protected function _write($data) {
		if (!$this->connected) {
			$message = 'No connecting found while writing data to socket.';
			throw new RuntimeException($message);
		}

		$data .= "\r\n";
		return fwrite($this->_connection, $data, strlen($data));
	}

	/**
	 * 从套接字读取数据包。 在从套接字读取之前，将检查连接的可用性。
	 * @param integer $length 读取到的数据长度（bytes）
	 * @return string|boolean 当为字符串时表示读取成功，`false`时表示读取失败。
	 */
	protected function _read($length = null) {
		if (!$this->connected) {
			$message = 'No connection found while reading data from socket.';
			throw new RuntimeException($message);
		}
		if ($length) {
			if (feof($this->_connection)) {
				return false;
			}
			$data = stream_get_contents($this->_connection, $length + 2);
			$meta = stream_get_meta_data($this->_connection);

			if ($meta['timed_out']) {
				$message = 'Connection timed out while reading data from socket.';
				throw new RuntimeException($message);
			}
			$packet = rtrim($data, "\r\n");
		} else {
			$packet = stream_get_line($this->_connection, 16384, "\r\n");
		}
		return $packet;
	}

	// Producer Commands ===========================================================================

	/**
	 * 插入一个job到队列
     *
     * @param  integer  $pri      为优先级，可以为0-2^32（4,294,967,295），值越小优先级越高，默认为1024。
	 * @param  integer  $delay    延迟ready的秒数，在这段时间job为delayed状态。
	 * @param  integer  $ttr      (time to run ) 允许worker执行的最大秒数，如果worker在这段时间不能delete，release，bury
     *                                           job，那么job超时，服务器将release此job，此job的状态迁移为ready。最小为1秒，
     *                                           如果客户端指定为0将会被重置为1。
	 * @param  string   $data     这个job的数据
	 * @return int|boo            `false` 为插入失败，整数时表示插入成功，这个整数就是job的ID
	 */
	public function put($pri, $delay, $ttr, $data) {
		$this->_write(sprintf("put %d %d %d %d\r\n%s", $pri, $delay, $ttr, strlen($data), $data));
		$status = strtok($this->_read(), ' ');

		switch ($status) {
			case 'INSERTED':
			case 'BURIED':
				return (integer) strtok(' '); // job id
			case 'EXPECTED_CRLF':
			case 'JOB_TOO_BIG':
			default:
				$this->_error($status);
				return false;
		}
	}

	/**
     * `use`命令用于生产者。 随后put命令将作业放置到由此命令指定的管。 如果没有使用命令，作业将被放入名为`default`的管道中。
     * @param  string   $tube 一个名字最多200字节。 它指定要使用的管。 如果管不存在，它将被创建。
	 * @return str|boo  `false`时表示使用这个管道出错。
	 */
	public function useTube($tube) {
		$this->_write(sprintf('use %s', $tube));
		$status = strtok($this->_read(), ' ');

		switch ($status) {
			case 'USING':
				return strtok(' ');
			default:
				$this->_error($status);
				return false;
		}
	}

	/**
     * 使指定的管道内, 今后所添加的新job在指定的$delay时间后才能被reserve()取出（预订）来处理。
	 *
	 * @param string $tube 要暂停的管道名称
	 * @param integer $delay  要暂停的时间（秒）
	 * @return boolean 失败时为`false`
	 */
	public function pauseTube($tube, $delay) {
		$this->_write(sprintf('pause-tube %s %d', $tube, $delay));
		$status = strtok($this->_read(), ' ');

		switch ($status) {
			case 'PAUSED':
				return true;
			case 'NOT_FOUND':
			default:
				$this->_error($status);
				return false;
		}
	}

	// Worker Commands =============================================================================

	/**
	 * 取出（预订）一个job，待处理。
     * @param integer $timeout 设置取job的超时时间，timeout设置为0时，服务器立即响应或者TIMED_OUT，
     *                         积极的设置超时，将会限制客户端阻塞在取job的请求的时间
	 * @return array|false `false` 时失败。
	 */
	public function reserve($timeout = null) {
		if (isset($timeout)) {
			$this->_write(sprintf('reserve-with-timeout %d', $timeout));
		} else {
			$this->_write('reserve');
		}
		$status = strtok($this->_read(), ' ');

		switch ($status) {
			case 'RESERVED':
				return [
					'id' => (integer) strtok(' '),
					'body' => $this->_read((integer) strtok(' '))
				];
			case 'DEADLINE_SOON':
			case 'TIMED_OUT':
			default:
				$this->_error($status);
				return false;
		}
	}

	/**
	 * 从队列中删除一个job
	 *
	 * @param integer $id 这个job 的ID
	 * @return boolean `false` 时失败
	 */
	public function delete($id) {
		$this->_write(sprintf('delete %d', $id));
		$status = $this->_read();

		switch ($status) {
			case 'DELETED':
				return true;
			case 'NOT_FOUND':
			default:
				$this->_error($status);
				return false;
		}
	}

	/**
	 * 将一个reserve()后的job放回到ready或delayed队列（也就是在reserve()后用release()，而且前后都不能用delete()）
	 * @param  integer  $id      这个job 的ID
	 * @param  integer  $pri     job的优先级
	 * @param  integer  $delay   延迟ready的秒数
	 * @return boolean           `false`表示失败。
	 */
	public function release($id, $pri, $delay) {
		$this->_write(sprintf('release %d %d %d', $id, $pri, $delay));
		$status = $this->_read();

		switch ($status) {
			case 'RELEASED':
			case 'BURIED':
				return true;
			case 'NOT_FOUND':
			default:
				$this->_error($status);
				return false;
		}
	}

	/**
     * 将一个被reserve()取出（预订）后的job放入到buried状态，并且它会被放入FIFO链接列表中，
     * 直到客户端kick这些job，不然它们不会被处理。
	 *
	 * @param integer $id 这个job 的ID
	 * @param integer $pri 重新指定这个job的优先级
	 * @return boolean `false` 表示失败
	 */
	public function bury($id, $pri) {
		$this->_write(sprintf('bury %d %d', $id, $pri));
		$status = $this->_read();

		switch ($status) {
			case 'BURIED':
				return true;
			case 'NOT_FOUND':
			default:
				$this->_error($status);
				return false;
		}
	}

	/**
     * 允许worker请求更多的时间执行job，这个很有用当job需要很长的时间来执行，
     * worker可用周期的告诉服务器它仍然在执行job
     *
	 * @param integer $id   指定的job ID
	 * @return boolean `false` 表示失败
	 */
	public function touch($id) {
		$this->_write(sprintf('touch %d', $id));
		$status = $this->_read();

		switch ($status) {
			case 'TOUCHED':
				return true;
			case 'NOT_TOUCHED':
			default:
				$this->_error($status);
				return false;
		}
	}

	/**
     * 添加监控的tube到watch list列表，reserve指令将会从监控的tube列表获取job，
     * 对于每个连接，监控的列表默认为default
     *
	 * @param string $tube  管道名
	 * @return integer|boolean `false` 表示添加失败
	 */
	public function watch($tube) {
		$this->_write(sprintf('watch %s', $tube));
		$status = strtok($this->_read(), ' ');

		switch ($status) {
			case 'WATCHING':
				return (integer) strtok(' ');
			default:
				$this->_error($status);
				return false;
		}
	}

	/**
	 * consumers消费者可以通过发送ignore()来取消监控tube（也就是在watch()之后reserve()之前做） 
     *
	 * @param string $tube  管道名
	 * @return integer|boolean `false` 表示移出失败
	 */
	public function ignore($tube) {
		$this->_write(sprintf('ignore %s', $tube));
		$status = strtok($this->_read(), ' ');

		switch ($status) {
			case 'WATCHING':
				return (integer) strtok(' ');
			case 'NOT_IGNORED':
			default:
				$this->_error($status);
				return false;
		}
	}

	// Other Commands ==============================================================================

	/**
	 * 让client在系统中检查job，返回id对应的job
     *
	 * @param integer $id  知道的job ID
	 * @return string|boolean `false` 表示失败
	 */
	public function peek($id) {
		$this->_write(sprintf('peek %d', $id));
		return $this->_peekRead();
	}

	/**
	 * 让client在系统中检查job，获取最早一个处于“Ready”状态的job、注意、只能获取当前tube的job
	 *
	 * @return string|boolean `false` 表示失败
	 */
	public function peekReady() {
		$this->_write('peek-ready');
		return $this->_peekRead();
	}

	/**
     * 让client在系统中检查job，获取最早一个处于“Delayed”状态的job、注意、只能获取当前tube的job
	 *
	 * @return string|boolean `false` 表示失败
	 */
	public function peekDelayed() {
		$this->_write('peek-delayed');
		return $this->_peekRead();
	}

	/**
     * 让client在系统中检查job，获取最早一个处于“Buried”状态的job、注意、只能获取当前tube的job
     *
	 * @return string|boolean `false` 表示失败
	 */
	public function peekBuried() {
		$this->_write('peek-buried');
		return $this->_peekRead();
	}

	/**
	 * Handles response for all peek methods.
	 *
	 * @return string|boolean `false` on error otherwise the body of the job.
	 */
	protected function _peekRead() {
		$status = strtok($this->_read(), ' ');

		switch ($status) {
			case 'FOUND':
				return [
					'id' => (integer) strtok(' '),
					'body' => $this->_read((integer) strtok(' '))
				];
			case 'NOT_FOUND':
			default:
				$this->_error($status);
				return false;
		}
	}

	/**
     * 它将当前tube中状态为Buried的job迁移为ready状态，一次最多迁移$bound个。
     *
	 * @param integer $bound 唤醒的job上限数
	 * @return integer|boolean False 表示出错；返回数字时表示被唤醒的job数。
	 */
	public function kick($bound) {
		$this->_write(sprintf('kick %d', $bound));
		$status = strtok($this->_read(), ' ');

		switch ($status) {
			case 'KICKED':
				return (integer) strtok(' ');
			default:
				$this->_error($status);
				return false;
		}
	}

	/**
     * 它将当前tube中状态为Buried或Delayed的job迁移为ready状态。
     *
	 * @param integer $id 指定的 job ID.
	 * @return boolean `false` 表示失败
	 */
	public function kickJob($id) {
		$this->_write(sprintf('kick-job %d', $id));
		$status = strtok($this->_read(), ' ');

		switch ($status) {
			case 'KICKED':
				return true;
			case 'NOT_FOUND':
			default:
				$this->_error($status);
				return false;
		}
	}

	// Stats Commands ==============================================================================

	/**
     * 获取指定job 的所有状态信息
	 *
	 * @param integer $id 指定的 job ID.
	 * @return string|boolean `false`表示出错，否则是一个带有yaml格式的字典的字符串。
	 */
	public function statsJob($id) {
		$this->_write(sprintf('stats-job %d', $id));
		return $this->_statsRead();
	}

	/**
     * 获取管道的状态信息
	 *
	 * @param string $tube  管道的名字
	 * @return string|boolean `false`表示出错，否则是一个带有yaml格式的字典的字符串。
	 */
	public function statsTube($tube) {
		$this->_write(sprintf('stats-tube %s', $tube));
		return $this->_statsRead();
	}

	/**
	 * 获取整个消息队列系统的整体信息
	 *
	 * @return string|boolean `false`表示出错，否则是一个带有yaml格式的字典的字符串。
	 */
	public function stats() {
		$this->_write('stats');
		return $this->_statsRead();
	}

	/**
	 * 返回所有存在的管道列表
	 *
	 * @return string|boolean `false`表示出错，否则是一个带有yaml格式的字典的字符串。
	 */
	public function listTubes() {
		$this->_write('list-tubes');
		return $this->_statsRead();
	}

	/**
     * 返回生产者当前正在使用的管道。
	 *
	 * @return string|boolean `false`表示出错，否则就是当前正在使用的管道名。
	 */
	public function listTubeUsed() {
		$this->_write('list-tube-used');
		$status = strtok($this->_read(), ' ');

		switch ($status) {
			case 'USING':
				return strtok(' ');
			default:
				$this->_error($status);
				return false;
		}
	}

	/**
	 * 返回消息消费端当前正在监视的管道列表。
     *
	 * @return string|boolean `false`表示出错，否则是一个带有yaml格式的字典的字符串。
	 */
	public function listTubesWatched() {
		$this->_write('list-tubes-watched');
		return $this->_statsRead();
	}

	/**
	 * Handles responses for all stat methods.
	 *
	 * @param boolean $decode Whether to decode data before returning it or not. Default is `true`.
	 * @return array|string|boolean `false` on error otherwise statistical data.
	 */
	protected function _statsRead($decode = true) {
		$status = strtok($this->_read(), ' ');

		switch ($status) {
			case 'OK':
				$data = $this->_read((integer) strtok(' '));
				return $decode ? $this->_decode($data) : $data;
			default:
				$this->_error($status);
				return false;
		}
	}

	/**
	 * Decodes YAML data. This is a super naive decoder which just works on
	 * a subset of YAML which is commonly returned by beanstalk.
	 *
	 * @param string $data The data in YAML format, can be either a list or a dictionary.
	 * @return array An (associative) array of the converted data.
	 */
	protected function _decode($data) {
		$data = array_slice(explode("\n", $data), 1);
		$result = [];

		foreach ($data as $key => $value) {
			if ($value[0] === '-') {
				$value = ltrim($value, '- ');
			} elseif (strpos($value, ':') !== false) {
				list($key, $value) = explode(':', $value);
				$value = ltrim($value, ' ');
			}
			if (is_numeric($value)) {
				$value = (integer) $value == $value ? (integer) $value : (float) $value;
			}
			$result[$key] = $value;
		}
		return $result;
	}
}

?>
