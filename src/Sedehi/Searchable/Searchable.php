<?php namespace Sedehi\Searchable;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;

trait Searchable
{

    public function scopeSearchable($query)
    {
        $dates = array_unique(array_merge(Config::get('searchable.date_fields'), $this->dates));


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
        if (Config::get('searchable.date_type') === 'gregorian') {
            return 'mktime';
        }else{
			if(!function_exists('jmktime')){
				throw new \Exception('jmktime functions are available');
			}
			return 'jmktime';
		}
		
		


        
    }

    private function convertDate($date)
    {

        $mktimeFunction = $this->mktime();
        $dateTime       = array();
        $dateTime[3]    = 0;
        $dateTime[4]    = 0;
        $dateTime[5]    = 0;
        $dateTime       = array_merge(explode(Config::get('searchable.date_divider'), $date), $dateTime);

        if (count($dateTime) == 6) {
            $timestamp = $mktimeFunction($dateTime[3], $dateTime[4], $dateTime[5], $dateTime[1], $dateTime[0],
                                         $dateTime[2]);

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
                $query->$data['clause']($data['column'], $data['value']);
                break;
        }
    }

}
