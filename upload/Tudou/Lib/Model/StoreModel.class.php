<?php
class StoreModel extends CommonModel{
    protected $pk = 'shop_id';
    protected $tableName = 'store';
    public function updateMonth($shop_id){
        $shop_id = (int) $shop_id;
        $month = date('Ym', NOW_TIME);
        $num = (int) D('Storeorder')->where(array('shop_id' => $shop_id, 'month' => $month))->count();
        return $this->execute("update " . $this->getTableName() . " set  month_num={$num} where shop_id={$shop_id}");
    }
    public function getStoreCate(){
        return array(
			'1' => '名烟名酒', 
			'2' => '保健品', 
			'3' => '早餐食品', 
			'4' => '甜点饮料', 
			'5' => '饼干', 
			'6' => '水果鲜花',
			'7' => '休闲小吃',
			'8' => '健康保健',
		);
    }
	public function get_file_Code($shop_id,$size){
        $url = U('wap/store/shop', array('shop_id' => $shop_id, 't' => NOW_TIME, 'sign' => md5($shop_id . C('AUTH_KEY') . NOW_TIME)));
        $token = 'shop_id_' . $shop_id;
        $file = ToQrCode($token, $url,$size);
        return $file;
    }
    public function CallDataForMat($items){
        if (empty($items)) {
            return array();
        }
        $obj = D('Shop');
        $shop_ids = array();
        foreach ($items as $k => $val) {
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        $shops = $obj->itemsByIds($shop_ids);
        foreach ($items as $k => $val) {
            $val['shop'] = $shops[$val['shop_id']];
            $items[$k] = $val;
        }
        return $items;
    }
}