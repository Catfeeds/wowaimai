<?php
class InformationAction extends CommonAction{
    public function index(){
        $u = D('Users');
        $ud = D('UserAddr');
        $bc = D('Connect');
        $map = array('user_id' => $this->uid);
        $res = $u->where($map)->find();
        $addr_count = $ud->where($map)->count();
        $rbc = $bc->where('uid =' . $this->uid)->select();
        $bind = array();
        foreach ($rbc as $val) {
            $bind[$val['type']] = $val;
        }
        $this->assign('res', $res);
        $this->assign('addr_count', $addr_count);
        $this->assign('bind', $bind);
        $this->display();
    }
	
    public function upload_face(){
        if (!$this->uid) {
            $this->ajaxReturn(array('status' => 'error', 'message' => '您没有登录或登录超时！'));
        } else {
            $avatar = I('avatar', '', 'trim,htmlspecialchars');
            if (!$avatar) {
                $this->ajaxReturn(array('status' => 'error', 'message' => '没有上传头像！'));
            } else {
                $u = D('Users');
                $up = $u->where('user_id =' . $this->uid)->setField('face', $avatar);
                if ($up) {
                    $this->ajaxReturn(array('status' => 'success', 'message' => '修改成功！'));
                } else {
                    $this->ajaxReturn(array('status' => 'error', 'message' => '修改失败！'));
                }
            }
        }
    }
    public function worker($worker_id = 0){
        if (empty($worker_id)) {
            $this->error('访问错误');
        }
        $worker = D('Shopworker')->find($worker_id);
        if (empty($worker)) {
            $this->error('访问错误');
        }
        if ($worker['user_id'] != $this->uid) {
            $this->error('没有权限访问错误');
        }
        if ($worker['status'] == 1) {
            $this->error('您已经同意过这条请求');
        }
        $shop = D('Shop')->find($worker['shop_id']);
        $this->assign('worker', $worker);
        $this->assign('shop', $shop);
        $this->display();
    }
    public function worker_agree($worker_id = 0) {
        if (empty($worker_id)) {
            $this->error('访问错误');
        }
        $worker = D('Shopworker')->find($worker_id);
        if (empty($worker)) {
            $this->error('访问错误');
        }
		if ($worker['status'] == 1) {
            $this->error('您已经确认过了');
        }
        if ($worker['user_id'] != $this->uid) {
            $this->error('没有权限访问错误');
        }
		if(D('Shopworker')->save(array('status' => 1, 'worker_id' => $worker['worker_id']))){
			$this->success('恭喜您成为了该商家的员工', U('worker/index/index'));
		}else{
			$this->error('操作失败');
		}
        
    }
    public function worker_refuse($worker_id = 0){
        if (empty($worker_id)) {
            $this->error('访问错误');
        }
        $worker = D('Shopworker')->find($worker_id);
        if (empty($worker)) {
            $this->error('访问错误');
        }
		if ($worker['status'] == 1) {
            $this->error('您不能执行此操作');
        }
        if ($worker['user_id'] != $this->uid) {
            $this->error('没有权限访问错误');
        }
		if(D('Shopworker')->where(array('worker_id' => $worker['worker_id']))->delete()){
			$this->success('您残忍地拒绝了该商家的请求', U('index/index'));
		}else{
			$this->error('操作失败');
		}
    }
	
	//会员等级购买
	public function buy(){
		$this->assign('ranks', D('Userrank')->fetchAll());
		$this->display();
		
	}
	public function pay($rank_id = 0){
        if($rank_id){
				if(!($detail = D('Userrank')->find($rank_id))){
					$this->ajaxReturn(array('code'=>'0','msg'=>'等级不存在'));
				}
				if($detail['price'] < 1) {
					$this->ajaxReturn(array('code'=>'0','msg'=>'购买金额配置错误'));
				}
				if($this->member['rank_id'] >= $detail['rank_id']) {
					$this->ajaxReturn(array('code'=>'0','msg'=>'不能降级或者你购买的等级跟当前等级一致'));
				}
				if($this->member['money'] < $detail['price']) {
					$this->ajaxReturn(array('code'=>'0','msg'=>'余额不足，请先充值'));
				}
				if(D('Users')->addMoney($this->uid,-$detail['price'],'购买等级'.$detail['rank_name'].'扣款')){
					D('Users')->save(array('user_id'=>$this->uid,'rank_id'=>$detail['rank_id']));
					if($this->_CONFIG['profit']['profit_buy_user_rank']){
						D('Userprofitlogs')->pay_rank_profit_user($this->uid,$detail['price'],$detail['rank_name']);//会员购买等级对接三级分销
					}
					$this->ajaxReturn(array('code'=>'1','msg'=>'购买等级'.$detail['rank_name'].'成功','url'=>U('member/index')));
				}else{
					$this->ajaxReturn(array('code'=>'0','msg'=>'购买失败'));
				}
		}else{
			$this->ajaxReturn(array('code'=>'0','msg'=>'访问错误'));
		}
    }
	
	
	
}