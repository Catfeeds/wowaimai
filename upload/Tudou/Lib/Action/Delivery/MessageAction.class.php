<?php
class MessageAction extends CommonAction{
	
	protected function _initialize(){
        parent::_initialize();
        $getMsgCate = D('Msg')->getMsgCate();
        $this->assign('getMsgCate', $getMsgCate);
    }
	
    public function index(){
        $this->display();
    }
    public function load(){
        $Msg = D('Msg');
        import('ORG.Util.Page');
		$map['cate_id'] = array('eq',5); 
		$map['closed'] = array('eq',0); 
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
		$lists = $Msg->where($map)->order(array('create_time' => 'desc'))->select();//时间降序排
        foreach ($lists as $k => $val) {
			 if (!empty($val['delivery_id'])) {
                $lists[$k]['delivery_id'] =  $val['delivery_id'];
                if ($lists[$k]['delivery_id'] != $this->delivery_id ) {
                    unset($lists[$k]);
                }
            }

        }
		$Page=new Page(count($lists),6);
	    $show = $Page->show(); // 分页显示输出
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }

        $list = array_slice($lists, $Page->firstRow, $Page->listRows);
        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->assign('types', $Msg->getType());//会员中心采用
        $this->display();
    }
  
    public function msgshow($msg_id){
        $msg_id = (int) $msg_id;
        D('Msg')->updateCount($msg_id, 'views');
        if (!($detail = D('Msg')->find($msg_id))) {
            $this->error('消息不存在');
        }
		if ($detail['cate_id'] != 5) {
            $this->error('类型错误');
        }	
		if (!empty($detail['delivery_id'])) {//如果表里面会员不为空那么判断ID正常不
            if ($detail['delivery_id'] != $this->delivery_id) {
            $this->error('您没有权限查看该消息');
        	}
        }
        
        if (!D('Msgread')->find(array('user_id' => $this->uid, 'msg_id' => $msg_id))) {
            D('Msgread')->add(array(
				'user_id' => $this->uid, 
				'msg_id' => $msg_id, 
				'create_time' => NOW_TIME, 
				'create_ip' => get_client_ip()
			));
        }
		//连接不等于空自动跳转
        if ($detail['link_url']) {
            header("Location:" . $detail['link_url']);
            die;
        }
        $this->assign('detail', $detail);
        $this->display();
    }
	
	 public function delete($msg_id = 0) {
        if (is_numeric($msg_id) && ($msg_id = (int) $msg_id)) {
            $obj = D('Msg');
            if (!$detail = $obj->find($msg_id)) {
                $this->tuMsg('请选择要删除的消息');
            }
            if ($detail['closed'] == 1) {
                $this->tuMsg('该消息不存在');
            }
			if ($detail['cate_id'] != 5) {
                $this->tuMsg('操作错误');
            }
			if (!empty($detail['delivery_id'])) {//如果表里面会员不为空那么判断ID正常不
				if ($detail['delivery_id'] != $this->delivery_id) {
					$this->tuMsg('您没有权限查看该消息');
				}
            }
            $obj->save(array('msg_id' => $msg_id, 'closed' => 1));
            $this->tuMsg('删除成功', U('message/index'));
        } else {
            $this->tuMsg('请选择要删除的消息');
        }
    }
}