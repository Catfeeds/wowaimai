<?php
class  ApiAction extends CommonAction{
	
	
	 public function getProvince(){
        $province = D('Paddlist')->field('id,name')->where(array('upid' =>0))->select();
        $res = array('status' => 1, 'msg' => '获取成功', 'result' => $province);
        exit(json_encode($res));
    }
	
	public function getRegionByParentId(){
        $parent_id = I('parent_id');
        $res = array('status' => 0, 'msg' => '获取失败，参数错误', 'result' => '');
        if($parent_id){
            $region_list = D('Paddlist')->field('id,name')->where(array('upid'=>$parent_id))->select();
            $res = array('status' => 1, 'msg' => '获取成功', 'result' => $region_list);
        }
        exit(json_encode($res));
    }
	
	public function dispatching() {        
        $goods_id = I('goods_id');
        $region_id = I('region_id');
		
		$Paddlists = D('Paddlist')->where(array('id'=>$region_id))->find();
		$Paddlist = D('Paddlist')->where(array('id'=>$Paddlists['upid']))->find();
		
	    $Pyunfeiprovinces = D('Pyunfeiprovinces')->where(array('id'=>$Paddlist['province_id']))->find();
		$Pyunfei = D('Pyunfei')->where(array('yunfei_id'=>$Pyunfeiprovinces['yunfei_id']))->find();
		
		$Goods = D('Goods')->where(array('kuaidi_id'=>$Pyunfei['kuaidi_id']))->find();
		
		if($Goods){
			if($Pyunfei['shouzhong']){
				$return_data['status'] = 1;
				$return_data['price'] = round($Pyunfei['shouzhong']/100,2);
			}else{
				$return_data['status'] = 1;
				$return_data['price'] = '免邮';
			}
		}else{
			$return_data['status'] = 1;
			$return_data['price'] = '无邮费';
		}
        exit(json_encode($return_data));
    }
	
	
	public function reminds(){
		$shop_id = (int) $this->_param('shop_id');
		$this->cronyes($order_id=0);//处理应该自动收货的订单
		
		if(IS_AJAX){
            session_start();
			if (empty($_SESSION['last_check'])){
				$_SESSION['last_check'] = time();
			}
			$map = array();
			$map['create_time'] = array(array('egt', $_SESSION['last_check']));      
			$map['status'] = 1;
			$map['shop_id'] = $shop_id;
			
			$Eleorder = D('Eleorder')->where($map)->select();
			
			$Marketorder = D('Marketorder')->where($map)->select();
			$Storeorder = D('Storeorder')->where($map)->select();
			
			$Order = D('Order')->where($map)->select();
			
			$maps = array();
			$maps['create_time'] = array(array('egt', $_SESSION['last_check']));   
			$Hotel = D('Hotel')->where(array('shop_id'=>$shop_id))->find();
			$maps['hotel_id'] =$Hotel['hotel_id'];
			$maps['order_status'] = 1;
			$Hotelorder = D('Hotelorder')->where($maps)->select();
			
			$query = array();
			$query['create_time'] = array(array('egt', $_SESSION['last_check']));   
			$query['shop_id'] = $shop_id;;
			$query['order_status'] = 1;
			$Bookingorder = D('Bookingorder')->where($query)->select();
			
			
			$condition = array();
			$condition['create_time'] = array(array('egt', $_SESSION['last_check']));   
			$condition['shop_id'] = $shop_id;;
			$condition['status'] = 1;
			$Tuanorder = D('Tuanorder')->where($condition)->select();
			
			if($Eleorder){
				$_SESSION['last_check'] = time();
				$this->ajaxReturn(array('status'=>'1','message'=>'有新的外卖订单了'));
			}elseif($Marketorder){
				$_SESSION['last_check'] = time();
				$this->ajaxReturn(array('status'=>'2','message'=>'有新的菜市场订单了'));
			}elseif($Storeorder){
				$_SESSION['last_check'] = time();
				$this->ajaxReturn(array('status'=>'2','message'=>'有新的便利店订单了'));
			}elseif($Order){
				$_SESSION['last_check'] = time();
				$this->ajaxReturn(array('status'=>'2','message'=>'有新的商城订单了'));
			}elseif($Hotelorder){
				$_SESSION['last_check'] = time();
				$this->ajaxReturn(array('status'=>'3','message'=>'有新的酒店订单了'));
			}elseif($Tuanorder){
				$_SESSION['last_check'] = time();
				$this->ajaxReturn(array('status'=>'3','message'=>'有新的团购订单了'));
			}elseif($Bookingorder){
				$_SESSION['last_check'] = time();
				$this->ajaxReturn(array('status'=>'4','message'=>'有新的订单了'));
			}else{
				$this->ajaxReturn(array('status'=>'0','message'=>'没有新的订单'));
			}
        }      
	}
	
	
		
    //自动确认订单，新增菜市场，便利店
    public function cronyes($order_id,$type = '0',$admin_id = '0'){
		
	    $CONFIG = D('Setting')->fetchAll();
		$time = time();
		
		$goods_time = (($CONFIG['site']['goods'] > 1) ? $CONFIG['site']['goods'] :'7')*24*3600;
		$order_time = $time - $goods_time; 
		$list = D('Order')->where(array('status'=>'2','create_time'=>array(array('ELT',$order_time))))->select();
		
        //商城订单
	    if(is_array($list)){
		  	  $k = 0;
			  foreach($list as $var){
					$date = true;
					if(!$detial = D('Order')->find($var['order_id'])){
						$date = false;
					}
					if($detial['status'] != 2){
					   $date = false;
					}
					$shop = D('Shop')->find($detial['shop_id']);
					if($shop['is_goods_pei'] != 1){
					    $do = D('DeliveryOrder')->where(array('type_order_id' =>$var['order_id'],'type' =>0)) -> find();
						if($do['status'] != 8){
							$date = false;
						}
					}
					if($date){
						 $k++;
						 D('Order')->save(array('order_id'=>$var['order_id'],'status'=>3));
						 D('Order')->overOrder($var['order_id']); //确认到账入口
					}
			 }
			$explain .= '已结算商城订单【'.$k.'】单';
        }
		
	    //外卖订单
	    $ele_time = (($CONFIG['site']['ele'] > 1) ? $CONFIG['site']['ele'] :'3')*3600;
		$eleorder_time = $time - $ele_time; 
		$list1 = D('Eleorder')->where(array('status'=>'2','create_time'=>array(array('ELT',$eleorder_time))))->select();
	
		if(is_array($list1)){
				$k1 = 0;
				foreach($list1 as $item){
					$dateele = true;
					if(!$detial = D('Eleorder')->find($item['order_id'])){
						$dateele = false;
					}
					$shop = D('Shop')->find($detial['shop_id']);
					if($shop['is_ele_pei'] == 0){
					   $do = D('DeliveryOrder')-> where(array('type_order_id' =>$item['order_id'],'type' =>1)) -> find();
						if($do['status'] == 2){
							$dateele = false;
						}
					}else{
						if($detial['status'] != 2){
							$dateele = false;
						}	
					}	
					if($dateele){
						$k1++;
						D('Eleorder')->overOrder($item['order_id']);
						D('Eleorder')->save(array('order_id' =>$item['order_id'], 'status' => 8,'end_time' => $time));
					}
				}
			$explain .= '已结算外卖订单【'.$k1.'】单';	
		 }
		 
		 //菜市场订单
	    $market_time = (($CONFIG['site']['market'] > 1) ? $CONFIG['site']['market'] :'3')*3600;
		$marketorder_time = $time - $market_time; 
		$list3 = D('Marketorder')->where(array('status'=>'2','create_time'=>array(array('ELT',$marketorder_time))))->select();
	
		if(is_array($list3)){
				$k3 = 0;
				foreach($list3 as $item){
					$dateele = true;
					if(!$detial = D('Marketorder')->find($item['order_id'])){
						$dateele = false;
					}
					$shop = D('Shop')->find($detial['shop_id']);
					if($shop['is_market_pei'] == 0){
					   $do = D('Marketorder')-> where(array('type_order_id' =>$item['order_id'],'type' =>3)) -> find();
						if($do['status'] == 2){
							$dateele = false;
						}
					}else{
						if($detial['status'] != 2){
							$dateele = false;
						}	
					}	
					if($dateele){
						$k3++;
						D('Marketorder')->overOrder($item['order_id']);
						D('Marketorder')->save(array('order_id' =>$item['order_id'], 'status' => 8,'end_time' => $time));
					}
				}
			$explain .= '已结算菜市场订单【'.$k3.'】单';	
		 }
		 
		//便利店订单
	    $store_time = (($CONFIG['site']['store'] > 1) ? $CONFIG['site']['store'] :'3')*3600;
		$storeorder_time = $time - $store_time; 
		$list4 = D('Storeorder')->where(array('status'=>'2','create_time'=>array(array('ELT',$storeorder_time))))->select();
	
		if(is_array($list4)){
				$k4 = 0;
				foreach($list4 as $item){
					$dateele = true;
					if(!$detial = D('Storeorder')->find($item['order_id'])){
						$dateele = false;
					}
					$shop = D('Shop')->find($detial['shop_id']);
					if($shop['is_store_pei'] == 0){
					   $do = D('DeliveryOrder')-> where(array('type_order_id' =>$item['order_id'],'type' =>4)) -> find();
						if($do['status'] == 2){
							$dateele = false;
						}
					}else{
						if($detial['status'] != 2){
							$dateele = false;
						}	
					}	
					if($dateele){
						$k4++;
						D('Storeorder')->overOrder($item['order_id']);
						D('Storeorder')->save(array('order_id' =>$item['order_id'], 'status' => 8,'end_time' => $time));
					}
				}
			$explain .= '已结算便利店订单【'.$k4.'】单';	
		 }
		  
	  
	  
	
		 if($type == 1 && $admin_id){
			  $arr['admin_id'] = $admin_id;
			  $arr['type'] = 1;
			  $arr['intro'] = $explain;
			  $arr['create_time'] = NOW_TIME;
			  $arr['create_ip'] = get_client_ip();
			  M('AdminActionLogs')->add($arr);  
		 }
		 
		 if($type == 1){
			 $this->tuSuccess($explain, U('admin/index/main')); 
		 }else{
			 return true; 
		 }
    }
	
	
	//抢单通知
	public function notice($id,$uid){
		
		$count = M('DeliveryOrder')->where(array('status'=>array('IN',array(0,1)),'closed'=>0,'type'=>0))->count();
		$count1 = M('DeliveryOrder')->where(array('status'=>array('IN',array(0,1)),'closed'=>0,'type'=>1))->count();
		$count3 = M('DeliveryOrder')->where(array('status'=>array('IN',array(0,1)),'closed'=>0,'type'=>3))->count();
		$count4 = M('DeliveryOrder')->where(array('status'=>array('IN',array(0,1)),'closed'=>0,'type'=>4))->count();
		
		$count2 = M('Running')->where(array('status'=>'1','closed'=>0))->count();
		
		if($count >= 1){
			$explain .= '您有待抢商城订单'.$count.'个';	
		}
		if($count1 >= 1){
			$explain .= '您有待抢外卖订单'.$count1.'个';	
		}
		if($count2 >= 1){
			$explain .= '您有待抢跑腿订单'.$count2.'个';	
		}
		if($count3 >= 1){
			$explain .= '您有待抢菜市场订单'.$count3.'个';	
		}
		if($count4 >= 1){
			$explain .= '您有待抢便利店订单'.$count4.'个';	
		}
		
		if($count >= 1 || $count1 >= 1  || $count2 >= 1  || $count3 >= 1  || $count4 >= 1){
			$this->ajaxReturn(array('code'=>1,'msg'=>$explain,'count'=>$count));
		}else{
			$this->ajaxReturn(array('code'=>0,'msg'=>'暂时没订单','count'=>0));
		}
	}
	
}