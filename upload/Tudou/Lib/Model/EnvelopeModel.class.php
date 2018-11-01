<?php
class EnvelopeModel extends CommonModel{
    protected $pk   = 'envelope_id';
    protected $tableName =  'envelope';
    
    public function getType(){
        return array(
			'1' => '平台红包', 
			'2' => '商家红包', 
		);
    }
	
	 public function getOrderType(){
        return array(
			'1' => '商城订单', 
			'2' => '外卖订单', 
			'3' => '家政订单', 
			'4' => '预定订单', 
			'5' => '商家收银台', 
			'6' => '平台红包',
		);
    }
}