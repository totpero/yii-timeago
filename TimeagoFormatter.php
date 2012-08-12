<?php
/*
 * Timeago formatter class for Yii framework
 * @author Alex G <gubarev.alex@gmail.com>
 * @version 0.1
 */
class TimeagoFormatter extends CFormatter
{
    /*
     * @var string name of locale
     */
    public $locale;
    /*
     * @var boolean allow future prefix in 'timeago' output
     */
	public $allowFuture = true;
    /*
     *  @var string date format pattern for 'time' formatter
     */
    public $dateFormat  = 'Y-m-d (H:i:s)';

    private $data;

    public function init()
    {
        if (empty($this->locale)) {
            $this->locale = Yii::app()->language;
        }
        $this->setLocale($this->locale);
        parent::init();
    }

    /*
     * Includes file with locale-specific data array. When locale isnt exists used default 'en' locale
     * @param string $locale locale name (like 'ru', 'en-short' etc.)
     */
    private function setLocale($locale)
    {
        $path = dirname(__FILE__).DIRECTORY_SEPARATOR.'locale'.DIRECTORY_SEPARATOR.$locale.'.php';
        if (!file_exists($path)) {
            $this->locale = 'en';
            $path = dirname(__FILE__).DIRECTORY_SEPARATOR.'locale'.DIRECTORY_SEPARATOR.$this->locale.'.php';
        }
        $this->data = require($path);
    }

    /*
     * Formats date string by $dateFormat pattern
     */
    public function formatTime($value)
    {
       	return date($this->dateFormat, strtotime($value));
    }

    /*
     * Formats value in timeago formatted string
     * @param mixed $value timestamp, DateTime or date-formatted string
     * @return string timeago formatted string
     */
    public function formatTimeago($value)
    {
        if ($value instanceof DateTime) {
            $time = date_timestamp_get($value);
        }else if (is_string($value)) {
            $time = strtotime($value);
        }

        return $this->inWords((time() - $time));
    }

    /*
     * Converts time delta to timeago formatted string
     * @param integer $seconds time delta in seconds
     * @return string timeago formatted string
     */
    public  function inWords($seconds)
    {
        $prefix = $this->data['prefixAgo'];
        $suffix = $this->data['suffixAgo'];
        if ($this->allowFuture && $seconds < 0) {
            $prefix = $this->data['prefixFromNow'];
            $suffix = $this->data['suffixFromNow'];
        }

        $minutes = $seconds / 60;
        $hours = $minutes / 60;
        $days = $hours / 24;
        $years = $days / 365;

        $separator = $this->data['wordSeparator'] === NULL ? " " : $this->data['wordSeparator'];

        $wordsConds  = array($seconds < 45, $seconds < 90, $minutes < 45, $minutes < 90, $hours < 24, $hours < 42, $days < 30, $days < 45, $days < 365, $years < 1.5, true);
        $wordResults = array(array('seconds', round($seconds)),
            array('minute', 1),
            array('minutes', round($minutes)),
            array('hour', 1),
            array('hours', round($hours)),
            array('day', 1),
            array('days', round($days)),
            array('month', 1),
            array('months', round($days / 30)),
            array('year', 1),
            array('years', round($years)));

        for ($i = 0; $i < $count = count($wordsConds); ++$i) {
            if ($wordsConds[$i]) {
                $key = $wordResults[$i][0];
                $number = $wordResults[$i][1];
                if (is_array($this->data[$key]) && is_callable($this->data['rules'])) {
                    $n = call_user_func($this->data['rules'], $wordResults[$i][1]);
                    $message = $this->data[$key][$n];
                }else {
                    $message = $this->data[$key];
                }
                return trim(implode($separator, array($prefix, preg_replace('/%d/i', $number, $message), $suffix)));
            }
        }
    }

}