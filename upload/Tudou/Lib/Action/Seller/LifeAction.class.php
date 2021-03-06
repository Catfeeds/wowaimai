<?php
class LifeAction extends CommonAction
{
    protected $lifecate = array();
    private $create_fields = array('title', 'city_id', 'cate_id', 'area_id', 'business_id', 'text1', 'text2', 'text3', 'num1', 'num2', 'select1', 'select2', 'select3', 'select4', 'select5', 'photo', 'contact', 'mobile', 'qq', 'addr','money','lng', 'lat');
    public function _initialize()
    {
        parent::_initialize();
        $life = (int) $this->_CONFIG['operation']['life'];
        if ($life == 0) {
            $this->error('此功能已关闭');
            die;
        }
        $this->lifecate = D('Lifecate')->fetchAll();
        $this->lifechannel = D('Lifecate')->getChannelMeans();
        $this->assign('lifecate', $this->lifecate);
        $this->assign('channel', $this->lifechannel);
    }
    public function index()
    {
        $Life = D('Life');
        import('ORG.Util.Page');
        $map = array('user_id' => $this->uid);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if ($keyword) {
            $map['qq|mobile|contact|title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $Life->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Life->where($map)->order(array('last_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('citys', D('City')->fetchAll());
        $this->assign('cates', D('Lifecate')->fetchAll());
        $this->assign('channelmeans', D('Lifecate')->getChannelMeans());
        $this->display();
    }
    public function urgent(){
        if (!($life_id = (int) $this->_get('life_id'))) {
            $this->tuMsg('参数错误');
        }
        if (!($detail = D('Life')->find($life_id))) {
            $this->tuMsg('参数错误');
        }
        if ($detail['user_id'] != $this->uid) {
            $this->tuMsg('参数错误');
        }
        $day = (int) $this->_get('day');
        $mday = 0;
        switch ($day) {
            case 7:
                $mday = $day = 7;
                break;
            default:
                $day = 30;
                $mday = 27;
                break;
        }
        $money = $mday * $this->_CONFIG['life']['urgent'] * 100;
        if ($this->member['money'] < $money) {
            $this->tuMsg('余额不足', U('user/money/index'));
        }
        $urgent_date = date('Y-m-d', NOW_TIME + $day * 86400);
        if ($detail['urgent_date'] > TODAY) {
            $urgent_date = date('Y-m-d', strtotime($detail['urgent_date']) + $day * 86400);
        }
        if (D('Users')->addMoney($this->uid, -$money, '加急信息' . $day . '天')) {
            D('Life')->save(array('urgent_date' => $urgent_date, 'life_id' => $life_id));
            $this->tuMsg('您的信息已经加急', U('life/index'));
        }
        $this->tuMsg('操作失败');
    }
    public function top()
    {
        if (!($life_id = (int) $this->_get('life_id'))) {
            $this->tuMsg('参数错误');
        }
        if (!($detail = D('Life')->find($life_id))) {
            $this->tuMsg('参数错误');
        }
        if ($detail['user_id'] != $this->uid) {
            $this->tuMsg('参数错误');
        }
        $day = (int) $this->_get('day');
        $mday = 0;
        switch ($day) {
            case 7:
                $mday = $day = 7;
                break;
            default:
                $day = 30;
                $mday = 27;
                break;
        }
        $money = $mday * $this->_CONFIG['life']['top'] * 100;
        if ($this->member['money'] < $money) {
            $this->tuMsg('余额不足', U('user/money/index'));
        }
        $top_date = date('Y-m-d', NOW_TIME + $day * 86400);
        if ($detail['top_date'] > TODAY) {
            $top_date = date('Y-m-d', strtotime($detail['top_date']) + $day * 86400);
        }
        if (D('Users')->addMoney($this->uid, -$money, '置顶信息' . $day . '天')) {
            D('Life')->save(array('top_date' => $top_date, 'life_id' => $life_id));
            $this->tuMsg('您的信息已经在同频道置顶了', U('life/index'));
        }
        $this->tuMsg('操作失败');
    }
    public function flush()
    {
        if (!($life_id = (int) $this->_get('life_id'))) {
            $this->tuMsg('参数错误');
        }
        if (!($detail = D('Life')->find($life_id))) {
            $this->tuMsg('参数错误');
        }
        if ($detail['user_id'] != $this->uid) {
            $this->tuMsg('参数错误');
        }
        if (NOW_TIME - $detail['last_time'] < 86400) {
            $this->tuMsg('您已经刷新过了');
        }
        if (NOW_TIME - $detail['last_time'] > 86400 * 30) {
            $this->tuMsg('该信息已经超过30天了，不能在进行免费刷新');
        }
        $data = array('life_id' => $life_id, 'last_time' => NOW_TIME);
        if ($detail['top_date'] < TODAY) {
            $data['top_date'] = TODAY;
        }
        if (D('Life')->save($data)) {
            $this->tuMsg('刷新成功!', U('life/index'));
        }
        $this->tuMsg('操作失败');
    }
    //删除
    public function del()
    {
        $life_id = I('life_id', '', 'intval,trim');
        if (!$life_id) {
            $this->tuMsg('没有选择');
        } else {
            $l = D('Life');
            $r = $l->where('life_id =' . $life_id)->delete();
            if ($r) {
                $this->tuMsg('删除成功');
            } else {
                $this->tuMsg('删除失败');
            }
        }
    }
    //编辑
    public function edit($life_id)
    {
        if ($life_id = (int) $life_id) {
            $obj = D('Life');
            if (!($detail = $obj->find($life_id))) {
                $this->tuMsg('请选择要编辑的生活信息');
            }
            if ($this->isPost()) {
                $data = $this->editCheck();
                $data['life_id'] = $life_id;
                $details = $this->_post('details', 'SecurityEditorHtml');
                $data['audit'] = 0;
                if ($words = D('Sensitive')->checkWords($details)) {
                    $this->tuMsg('商家介绍含有敏感词：' . $words);
                }
                if (false !== $obj->save($data)) {
                    if ($details) {
                        D('Lifedetails')->updateDetails($life_id, $details);
                    }
                    $photos = $this->_post('photos', false);
                    if (!empty($photos)) {
                        D('Lifephoto')->upload($life_id, $photos);
                    }
                    $this->tuMsg('操作成功', U('life/index'));
                }
                $this->tuMsg('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->assign('cates', D('Lifecate')->fetchAll());
                $this->assign('channelmeans', D('Lifecate')->getChannelMeans());
                $this->assign('cate', D('Lifecate')->find($detail['cate_id']));
                $this->assign('areas', D('Area')->fetchAll());
                $this->assign('business', D('Business')->fetchAll());
                $this->assign('ex', D('Lifedetails')->find($life_id));
                $this->assign('attrs', D('Lifecateattr')->order(array('orderby' => 'asc'))->where(array('cate_id' => $detail['cate_id']))->select());
                $this->assign('user', D('Users')->find($detail['user_id']));
                $this->assign('photos', D('Lifephoto')->getPics($life_id));
                $this->display();
            }
        } else {
            $this->tuMsg('请选择要编辑的生活信息2');
        }
    }
    private function editCheck()
    {
        $data = $this->_post('data', false);
        $data['title'] = htmlspecialchars($data['title']);
        if (empty($data['title'])) {
            $this->tuMsg('标题不能为空');
        }
        $data['cate_id'] = (int) $data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->tuMsg('分类不能为空');
        }
        $data['area_id'] = (int) $data['area_id'];
        if (empty($data['area_id'])) {
            $this->tuMsg('地区不能为空');
        }
        $data['business_id'] = (int) $data['business_id'];
        if (empty($data['business_id'])) {
            $this->tuMsg('商圈不能为空');
        }
        $data['lng'] = htmlspecialchars(trim($data['lng']));
        $data['lat'] = htmlspecialchars(trim($data['lat']));
        $data['text1'] = htmlspecialchars($data['text1']);
        $data['text2'] = htmlspecialchars($data['text2']);
        $data['text3'] = htmlspecialchars($data['text3']);
        $data['num1'] = (int) $data['num1'];
        $data['num2'] = (int) $data['num2'];
        $data['select1'] = (int) $data['select1'];
        $data['select2'] = (int) $data['select2'];
        $data['select3'] = (int) $data['select3'];
        $data['select4'] = (int) $data['select4'];
        $data['select5'] = (int) $data['select5'];
        $data['urgent_date'] = TODAY;
        $data['top_date'] = TODAY;
        $data['contact'] = htmlspecialchars($data['contact']);
        if (empty($data['contact'])) {
            $this->tuMsg('联系人不能为空');
        }
        $data['mobile'] = htmlspecialchars($data['mobile']);
        if (empty($data['mobile'])) {
            $this->tuMsg('电话不能为空');
        }
        if (!isMobile($data['mobile']) && !isPhone($data['mobile'])) {
            $this->tuMsg('电话格式不正确');
        }
        $data['qq'] = htmlspecialchars($data['qq']);
        $data['addr'] = htmlspecialchars($data['addr']);
        $data['views'] = (int) $data['views'];
		$data['money'] = (int) ($data['money']*100);
        $data['create_time'] = NOW_TIME;
        $data['last_time'] = NOW_TIME + 86400 * 30;
        $data['create_ip'] = get_client_ip();
        return $data;
    }
}