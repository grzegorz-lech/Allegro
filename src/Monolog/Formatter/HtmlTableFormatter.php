<?php

namespace Monolog\Formatter;

use Monolog\Formatter\FormatterInterface;
use Monolog\Logger;

/**
 * HtmlTableFormatter class.
 *
 * @author Alan Gabriel Bem <alan.bem@goldenline.pl>
 */
class HtmlTableFormatter implements FormatterInterface
{
	/**
	 * Formats a log record.
	 *
	 * @param  array $record A record to format
	 * @return mixed The formatted record
	 */
	public function format(array $record)
	{
		return $this->toTable(array($record));
	}

	/**
	 * Formats a set of log records.
	 *
	 * @param  array $records A set of records to format
	 * @return mixed The formatted set of records
	 */
	public function formatBatch(array $records)
	{
		return $this->toTable($records);
	}

	private function toTable(array $records)
	{
		$table =
			<<<HEADER
               <table border="1" cellpadding="3" cellspacing="0">
                <thead>
                    <tr>
                        <th>Channel</th>
                        <th>Level</th>
                        <th>Message</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
HEADER;

		foreach ($records as $record) {
			$table .= $this->toRow($record);
		}

		$table .=
			<<<FOOTER
                   </tbody>
            </table>
FOOTER;

		return $table;
	}

	private function toRow(array $record)
	{
		$channel = $record['channel'];
		$level = $record['level'];
		$levelName = $record['level_name'];
		$message = $record['message'];
		$datetime = $record['datetime']->format('Y-m-d H:i:s');

		switch ($level) {
			case Logger::WARNING:
			case Logger::ERROR:
				$color = '#e1b7ba';
				break;
			case Logger::CRITICAL:
			case Logger::ALERT:
			case Logger::EMERGENCY:
				$color = '#ff0000';
				break;
			default:
				$color = '#ffffff';
		}

		$row  =
			<<<ROW
                       <tr>
                        <th bgcolor="$color">$channel</th>
                        <td bgcolor="$color">$levelName</td>
                        <td bgcolor="$color">$message</td>
                        <td bgcolor="$color">$datetime</td>
                    </tr>
ROW;

		return $row;
	}
}