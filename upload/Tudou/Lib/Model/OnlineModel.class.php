<?php
class OnlineModel extends CommonModel{
    protected $pk = 'online_id';
    protected $tableName = 'online';
	
	
	
	//生成报表
    public function buildDailyList($shop_id){
		
		$config = D('Setting')->fetchAll();
		$date = date("Y-m-d",time());//格式化时间戳
		//$bg_time = strtotime(TODAY);
		//$condition['create_time'] = array(array('ELT', NOW_TIME), array('EGT', $bg_time));
		$condition['status'] = array('IN',array(1,2,8));//已付款订单
		
		//单个商家生成日报表
		if($shop_id){
			$Shop = D('Shop')->find($shop_id);
			
			$condition['shop_id'] = $shop_id;
			$arr = D('Ordergoods')->where($condition)->select();//单个商家查看数据
			$result = D('Online')->where(array('shop_id'=>$shop_id,'rptDate'=>$date))->find();
			//如果有数据，商家开启了电商，今日没有生成过数据
			if(!empty($arr) && ($Shop['is_online'] == 1) && empty($result)){
				$data = array();
				$data['shop_id'] = $Shop['shop_id'];//商家ID
				$data['code'] = $config['online']['userId'].''.$Shop['shop_id'];//站点编码
				$data['name'] = $Shop['shop_name'];//站点编码
				$data['countyType'] = $config['online']['countyType'];//站点类型
				$data['buyOrder'] = D('Ordergoods')->where($condition)->count();//代买总订单
				$data['saleOrder'] = D('Ordergoods')->where($condition)->count();//代卖总订单
				$data['data'] = $this->getGoodsAttrList($condition,$arr);
				if(!empty($data['data'])){
					D('Online')->saveData($data);
					return true;	
				}
				return false;
			}
			return false;
		}
		
		if($shop_id == 0 || $shop_id == ''){
			//批量生成
			$shop_list = D('Shop')->where(array('is_online'=>1))->select();
			$i = 0;
			foreach ($shop_list as $kk => $vv){
				$condition['shop_id'] = $vv['shop_id'];
				$arr = D('Ordergoods')->where($condition)->select();
				$result = D('Online')->where(array('shop_id'=>$vv['shop_id'],'rptDate'=>$date))->find();
				//有东西就上报
				if(!empty($arr) && empty($result)){
					$data = array();
					$data['shop_id'] = $vv['shop_id'];//商家ID
					$data['code'] = $config['online']['userId'].''.$vv['shop_id'];//站点编码
					$data['name'] = $vv['shop_name'];//站点编码
					$data['countyType'] = $config['online']['countyType'];//站点类型
					$data['buyOrder'] = D('Ordergoods')->where($condition)->count();//代买总订单
					$data['saleOrder'] = D('Ordergoods')->where($condition)->count();//代卖总订单
					$data['data'] = $this->getGoodsAttrList($condition,$arr);
					if(!empty($data['data'])){
						$i++;//这里有数据算一条
						D('Online')->saveData($data);
					}
				}
			}
			if($i > 0){
				return $i;	
			}else{
				return false;
			}
		}
		return false;
    }
	
	//格式化日报表
    public function saveData($data){
		$config = D('Setting')->fetchAll();
		$data = $data;
		$data['userId'] = $config['online']['userId'];
		$data['rptDate'] = date("Y-m-d",time());
		$data['status'] = 0;
		$data['create_time'] = time();
        $data['create_ip'] = get_client_ip();
		D('Online')->add($data);
	}
	
	
	//获取商品类别数据
    public function getGoodsAttrList($condition,$arr){
		$cate_ids = array();
		foreach ($arr as $val){
			if($val['cate_id']){
				$cate_ids[$val['cate_id']] = $val['cate_id'];
			}
        }
		
		$obj = array();
		foreach ($cate_ids as $v){
			if($res = D('Goodscate')->find($v)){
				if($res['commId']){
					$condition['cate_id'] = $v;
					$obj[$v]['commId'] = $res['commId'];
					$obj[$v]['money'] = D('Ordergoods')->where($condition)->sum('price');
				}
			}
        }
		if(count($obj) >= 1){
			$data = serialize($obj);
			return $data;
		}else{
			return false;
		}
    }
	
	
	//推送报表,按照报表推送
    public function pushDailyList($online_ids,$type){
		
		$date = date("Y-m-d",time());//格式化时间戳
		$condition['rptDate'] = $date;
		$condition['status'] = '0';

		if(is_array($online_ids) && $type == 0){
			//选择推送
			$condition['online_id'] = array('IN',$online_ids);
            $arr = D('Online')->where($condition)->select();
        }else{
			if($type  == 1){
				//全部推送
				$arr = D('Online')->where($condition)->select();
			}else{
				//单条推送
				$condition['online_id'] = $online_ids;
				$arr = D('Online')->where($condition)->select();
			}
        }
		
		if($arr){
			$xml = $this->getArray($arr);
			$return = $this->asynServiceStationData($xml);
			if($return == 1){
				$i = 0;
				foreach($arr as $key=>$val){
					$i++;
					D('Online')->where(array('online_id'=>$val['online_id']))->save(array('status'=>1,'return'=>$return,'push_time'=>time(),'push_ip'=>get_client_ip()));
				}
				if($i > 0){
					return $i;
				}
				return true;	
			}
			return false;
		}else{
			return false;
		}
		return false;
    }
	
	//获取xml
    public function getArray($arr){
		$config = D('Setting')->fetchAll();//全站数据
		$data = array();
		foreach($arr as $k=>$v){
		  $array[$k]['code'] = $config['online']['userId'].''.$v['shop_id'];
		  $array[$k]['name'] = $v['name'];
		  $array[$k]['countyType'] = $v['countyType'];
		  $array[$k]['buyOrder'] = $v['buyOrder'];
		  $array[$k]['saleOrder'] = $v['saleOrder'];
          $datas = unserialize($v['data']);
		  foreach($datas as $kk=>$vv){
			 $array[$k]['arr'][$kk]['commId'] = $vv['commId'];
			 $array[$k]['arr'][$kk]['money'] = round($vv['money']/100,2);    
		  }
        }
		$data['userId'] = 
		$data['ptDate'] = date("Y-m-d",time());
		$data['serviceStationReport'] = $array;
		$xml ="<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
		$xml .= "<serviceStation>\n";
		$xml .= "<userId>" . $config['online']['userId'] . "</userId>\n";
		$xml .= "<rptDate>" . date("Y-m-d",time()) . "</rptDate>\n";
			foreach ($array as $val) {
				$xml .= $this->createXml($val);
			}
		$xml .= "</serviceStation>\n";
		return $xml;
		
	}
	//生成xml
	public function createXml($val){
		$item = "<serviceStationReport>\n";
		$item .= "<code>" . $config['online']['userId'].''.$val['code'] . "</code>\n";
		$item .= "<name>" . $val['name'] . "</name>\n";
		$item .= "<countyType>" . $val['countyType'] . "</countyType>\n";
		$item .= "<buyOrder>" . $val['buyOrder'] . "</buyOrder>\n";
		$item .= "<saleOrder>" . $val['saleOrder'] . "</saleOrder>\n";
			foreach ($val['arr'] as $v){
				$item .= "<serviceStationCommodity>\n";
				$item .= "<commId>" . $v['commId'] . "</commId>\n";
				$item .= "<money>" . $v['money'] . "</money>\n";
				$item .= "</serviceStationCommodity>\n";
			}
		
		$item .= "</serviceStationReport>\n";
		return $item;
	}	
	
	
	//异步提交【站点】数据
	private function asynServiceStationData($requestStr){
		//p($requestStr);die;
		//站点提交字符串
		$soapUrlStr = "http://211.88.20.132:8040/services/syncServiceStation?wsdl";
		//站点提交方法
		$upMothodStr = 'syncServiceStationOperation';
		try{
			$client = new SoapClient($soapUrlStr,array ('trace'=>0,'uri'=>' http://211.88.20.132:8040/'));
			$client->soap_defencoding = 'utf-8';  
			$client->decode_utf8 = false;   
			$client->xml_encoding = 'utf-8';
			$param = array('in'=>$requestStr);
			$responseXmlStr = $client->__Call($upMothodStr,array($param));
			
			$ret = 0;
			if($responseXmlStr->states == false){
				$ret = 1;
			}else{
				$ret = 0;
			}
			// 保存 站点数据 数据库
			return $ret;
		}catch(SOAPFault $e){
			return $e;
		}
	}

	
}