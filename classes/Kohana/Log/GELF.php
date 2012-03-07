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

	protected $_hostname = '127.0.0.1';
	protected $_port = 12201;

	/**
	 * Creates a new GELF logger.
	 *
	 *     $writer = new Log_File($hostname, $port);
	 *
	 * @param   string  $hostname  Graylog2 Server Hostname
	 * @param   int     $port      Graylog2 Server Port
	 * @return  void
	 */
	public function __construct($hostname = '127.0.0.1', $port = 12201)
	{
		$this->_hostname = $hostname;
		$this->_port = $port;
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
				$message = new GELFMessage();

				$message->setTimestamp($message['time']);
				$message->setLevel($message['level']);
				$message->setShortMessage($message['body']);
				$message->setFullMessage($message['trace']);
				$message->setFile($message['file']);
				$message->setLine($message['line']);
				$message->setAdditional('class', $message['class']);
				$message->setAdditional('function', $message['function']);
				
				foreach ($message['additional'] as $key => $value)
				{
					$message->setAdditional($key, $value);
				}

				$publisher->publish($message);
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