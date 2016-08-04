<?php namespace Sedehi\Searchable;

use ClassPreloader\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;

trait Searchable
{

    public function scopeSearchable($query,$searchable = null)
    {
        $dates = array_unique(array_merge(config('searchable.date_fields'), $this->dates));
        
        if(!is_null($searchable)){
            $this->searchable = $searchable;
        }

        foreach ($this->searchable as $key => $value) {

            if (is_numeric($key)) {
                $key = $value;
            }
            $data['operator'] = '=';
            $data['clause']   = 'where';
            $data['column']   = $key;
            $data['value']    = Request::get($key);
            $append           = false;

            if (in_array($key, $dates)) {
                $data['value'] = $this->convertDate(Request::get($key));
            }

            if (is_array($value)) {

                if (isset($value['operator'])) {
                    $data['operator'] = strtoupper($value['operator']);
                }

                if (isset($value['between'])) {
                    if (is_array($value['between'])) {
                        $data['clause'] = 'whereBetween';
                        foreach ($value['between'] as $vBetween) {
                            if (Request::has($vBetween)) {
                                if (in_array($key, $dates)) {
                                    $data['value'][] = $this->convertDate(Request::get($vBetween));
                                } else {
                                    $data['value'][] = Request::get($vBetween);
                                }

                                $append = true;
                            }
                        }
                    }
                }
            }

            if ($data['operator'] == 'LIKE') {
                $data['value'] = '%'.$data['value'].'%';
            }

            if (Request::has($key)) {
                $append = true;
            }


            if ($append) {
                $this->clause($query, $data);
            }
        }
    }

    private function mktime()
    {
        if (config('searchable.date_type') === 'gregorian') {
            return 'mktime';
        } else {
            if (!function_exists('jmktime')) {
                throw new \Exception('jmktime functions are unavailable');
            }

            return 'jmktime';
        }
    }

    private function convertDate($date)
    {

        $mktimeFunction = $this->mktime();
        $dateTime       = [];
        $dateTime[3]    = 0;
        $dateTime[4]    = 0;
        $dateTime[5]    = 0;
        $dateTime       = array_merge(explode(config('searchable.date_divider'), $date), $dateTime);
        $formats        = ['d' => 0, 'm' => 1, 'y' => 2, 'h' => 3, 'i' => 4, 's' => 5];

        if (count($dateTime) == 6) {
            if (!is_null(config('searchable.date_format'))) {
                $formats = array_flip(explode(config('searchable.date_divider'), config('searchable.date_format')));
            }
            $timestamp = $mktimeFunction($dateTime[$formats['h']], $dateTime[$formats['i']], $dateTime[$formats['s']],
                                         $dateTime[$formats['m']], $dateTime[$formats['d']], $dateTime[$formats['y']]);

            return date($this->getDateFormat(), $timestamp);
        }


        return false;
    }

    private function clause($query, $data)
    {
        switch ($data['clause']) {
            case 'where':
                $query->$data['clause']($data['column'], $data['operator'], $data['value']);
                break;
            case 'whereBetween':
                if (count($data['value']) == 2) {
                    $query->$data['clause']($data['column'], $data['value']);
                }
                break;
        }
    }
}
