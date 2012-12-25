<?php

	/**
	 * Preapre date-time for MySql
	 *
	 * @param string $date
	 *
	 * @return Date
	 */
	function mysql_date_format($date)
	{
		return date('Y-m-d H:i:s', strtotime($date));
	}

	/**
	 * Get var_dump as a string
	 *
	 * @param mixed $data
	 *
	 * @return string
	 */
	function capture($data)
	{
		ob_start();
		var_dump($data);
		return ob_get_clean();
	}

?>