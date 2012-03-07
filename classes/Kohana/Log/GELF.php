<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * GELF log writer. Writes out messages to a Graylog2 server.
 *
 * @package    Kohana
 * @category   Logging
 * @author     Kiall Mac Innes
 * @copyright  (c) 2012 Managed I.T.
 */
class Kohana_Log_GELF extends Log_Writer {

	protected $_hostname;
	protected $_port;
	protected $_facility;

	/**
	 * Creates a new GELF logger.
	 *
	 *     $writer = new Log_File($hostname, $port, $facility);
	 *
	 * @param   string  $hostname  Graylog2 Server Hostname
	 * @param   int     $port      Graylog2 Server Port
	 * @param   string  $facility  Graylog2 Facility
	 * @return  void
	 */
	public function __construct($hostname = '127.0.0.1', $port = 12201, $facility = 'Kohana')
	{
		$this->_hostname = $hostname;
		$this->_port = $port;
		$this->_facility = $facility;
	}

	/**
	 * Writes each of the messages to Graylog2.
	 *
	 *     $writer->write($messages);
	 *
	 * @param   array   messages
	 * @return  void
	 */
	public function write(array $messages)
	{
		try
		{
			require_once Kohana::find_file('vendor/gelf', 'GELFMessage');
			require_once Kohana::find_file('vendor/gelf', 'GELFMessagePublisher');

			$publisher = new GELFMessagePublisher($this->_hostname, $this->_port);

			foreach ($messages as $message)
			{
				$gmessage = new GELFMessage();

				$host = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'Unknown';
				$facility = isset($message['additional']['facility']) ? isset($message['additional']['facility']) : $this->_facility;

				$gmessage->setHost($host);
				$gmessage->setTimestamp($message['time']);
				$gmessage->setLevel($message['level']);
				$gmessage->setShortMessage($message['body']);
				$gmessage->setFullMessage(var_export($message['trace'], TRUE));
				$gmessage->setFile($message['file']);
				$gmessage->setLine($message['line']);
				$gmessage->setAdditional('class', $message['class']);
				$gmessage->setAdditional('function', $message['function']);

				foreach ($message['additional'] as $key => $value)
				{
					$gmessage->setAdditional($key, $value);
				}

				$publisher->publish($gmessage);
			}
		}
		catch (Exception $e)
		{
			// Its likely too late to log another message, but no harm...
			Kohana::$log->add(Log::ALERT, "Writing Logs to GELF failed. Message: :message", array(
				':message'  => $e->getMessage(),
			), array(
				'exception' => $e
			));
		}
	}
} // End Kohana_Log_GELF