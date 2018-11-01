<?php
class UserscashModel extends CommonModel {
    protected $pk = 'cash_id';
    protected $tableName = 'users_cash';
	public function getError() {
        return $this->error;
    }
	
	
	
	
	
	
	//微信企业付款封装
	public function weixinUserCach($cash_id,$tpye){
		$detail = D('Userscash')->where(array('cash_id'=>$cash_id))->find();
		if(!$detail){
           $this->error = '提现的订单不存在';
		   return false;
        }
		if($detail['status'] !=0){
           $this->error = '提现订单状态不正确';
		   return false;
        }

		$money = $tpye =='user' ? $detail['money']: $detail['gold'];//获取金额
	
		if($money < 100){
			$this->error = '申请提现的金额不合法';
		    return false;
		}
		
		
		
		
		$payment = D('Payment')->getPayment('weixin');
		if(!$payment){
			$this->error = '网站没有配置微信支付';
		    return false;
		}
		$connect = D('Connect')->where(array('uid'=>$detail['user_id'],'type'=>weixin))->find();
		if(empty($connect['open_id'])){
			$this->error = '您没有关注微信或者不是微信登录';
		    return false;
		}
		include (APP_PATH . 'Lib/Payment/WxPayPubHelper/WxPayPubHelper.php');
			
		//调用请求接口基类
        $Redpack = new Withdrawals();
        $Redpack->setParameter('mch_appid', $payment['appid']);
        $Redpack->setParameter('mchid', $payment['mchid']);
        $Redpack->setParameter('partner_trade_no', $cash_id);//商户订单号
        $Redpack->setParameter('re_user_name',$detail['re_user_name']);//收款人姓名
        $Redpack->setParameter('amount', $money);//付款金额
        $Redpack->setParameter('desc','申请提现付款');
        $Redpack->setParameter('openid',$connect['open_id']);
        $Redpack->setParameter('check_name', 'NO_CHECK');
		
        $result = $Redpack->sendMerchantCash();
		
		
		if (is_array($result) && $result['result_code'] == 'SUCCESS'){
			
			$arr = array();
			$arr['mch_billno'] = $result['mch_billno'];
			$arr['return_msg'] = $result['return_msg'];
			$arr['payment_no'] = $result['payment_no'];
			$arr['partner_trade_no'] = $result['partner_trade_no'];
			$arr['mpayment_time'] = time();
			$arr['status'] = 1;
			D('Userscash')->where(array('cash_id'=>$cash_id))->save($arr);
			D('Weixintmpl')->weixin_cash_user($detail['user_id'],2);
			return true;
			//如果退款成功
		}else{
			$this->error = '退款失败:原因【'.$result['return_msg'] .''.$result['err_code_des'].'】';
			return false;
		}	
	
			
		
    }



    //检测分站的提现每天提现多少次
	public function check_cash_addtime($user_id,$type){
		$config = D('Setting')->fetchAll();
		$bg_time = strtotime(TODAY);
		
		if($type == 1){
			$count = $this->where(array('user_id'=>$user_id,'type'=>user,'addtime' => array(array('ELT', NOW_TIME), array('EGT', $bg_time))))->count();
			if($config['cash']['user_cash_second']){
				if($count > $config['cash']['user_cash_second']){
					return false;
				}
			}
			return true; 
		}elseif($type == 2){
			$count = $this->where(array('user_id'=>$user_id,'type'=>shop,'addtime' => array(array('ELT', NOW_TIME), array('EGT', $bg_time))))->count();
			if($config['cash']['shop_cash_second']){
				if($count > $config['cash']['shop_cash_second']){
					return false;
				}
			}
			return true;
		}else{
			return true;
		}

    }
}
