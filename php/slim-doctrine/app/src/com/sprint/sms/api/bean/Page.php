<?php
namespace App\com\sprint\sms\api\bean;

use \App\com\sprint\sms\api\support\FormatSupport;

class Page implements FormatSupport
{

	private $index;

	private $size;
	
	private $total;
	
	private $records;
	
	private $data; // List
	
	private $info; // Map
	
	public function getIndex()
    {
        return $this->index;
    }
	
	public function setIndex($index)
    {
        $this->index = (int)$index;
    }
	
	public function getSize()
    {
        return $this->size;
    }
	
	public function setSize($size)
    {
        $this->size = (int)$size;
    }
	
	public function getTotal()
    {
        return $this->total;
    }
	
	public function setTotal($total)
    {
        $this->total = (int)$total;
    }
	
	public function getRecords()
    {
        return $this->records;
    }
	
	public function setRecords($records)
    {
        $this->records = (int)$records;
		if ($this->records < 0) {
			$this->records = 0;
		}
		$this->total = $this->records > 0 ? ceil((float) $this->records / $this->size) : 0;
		if ($this->total == 0) {
			$this->index = 1;
		}
    }
	
	public function getData()
    {
        return $this->data;
    }
	
	public function setData($data)
    {
        $this->data = $data;
    }
	
	public function getInfo()
    {
        return $this->info;
    }
	
	public function setInfo($info)
    {
        $this->info = $info;
    }
	
	public function toFormatObject() {
		$o = array();
		$o['index'] = $this->index;
		$o['size'] = $this->size;
		$o['records'] = $this->records;
		$o['total'] = $this->total;	
		if (isset($this->info)) {
			$o['info'] = $this->info;
		}
		$data = $this->data;
		$count = isset($data) ? count($data) : 0;
		if ($count > 0 &&  $data[0] instanceof FormatSupport) {
			$arr = array();
			for ($i = 0; $i < $count; $i++) {				
				$arr[$i] = $data[$i]->toFormatObject();
			}
			$o['data'] = $arr;
		}		
		return $o;
	}
	
	/*
	 * STATIC
	 */	 
	public static function CREATE($index = 1, $size = 10) {
		$p = new self();
		$p->setIndex($index);
		$p->setSize($size);
		return $p;
	}
	
}
