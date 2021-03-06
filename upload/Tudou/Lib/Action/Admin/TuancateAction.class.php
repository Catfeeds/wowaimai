<?php
class TuancateAction extends CommonAction
{
    private $create_fields = array('cate_name', 'rate', 'orderby', 'photo');
    private $edit_fields = array('cate_name', 'rate', 'orderby', 'photo');
    public function index()
    {
        $obj = D('Tuancate');
        $list = $obj->fetchAll();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    public function select(){
        $obj = D('Tuancate');
        import('ORG.Util.Page');
        $map = array('closed' => array('IN', '0,-1'));
        if ($cate_name = $this->_param('cate_name', 'htmlspecialchars')) {
            $map['cate_name'] = array('LIKE', '%' . $cate_name . '%');
            $this->assign('cate_name', $cate_name);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 8);
        $pager = $Page->show();
        $list = $obj->where($map)->order(array('cate_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $pager);
        $this->display();
    }
    public function create($parent_id = 0)
    {
        if ($this->isPost()) {
            $data = $this->createCheck();
            $obj = D('Tuancate');
            $data['parent_id'] = $parent_id;
            if ($obj->add($data)) {
                $obj->cleanCache();
                $this->tuSuccess('添加成功', U('tuancate/index'));
            }
            $this->tuError('操作失败');
        } else {
            $this->assign('parent_id', $parent_id);
            $this->display();
        }
    }
    public function child($parent_id = 0)
    {
        $datas = D('Tuancate')->fetchAll();
        $str = '';
        foreach ($datas as $var) {
            if ($var['parent_id'] == 0 && $var['cate_id'] == $parent_id) {
                foreach ($datas as $var2) {
                    if ($var2['parent_id'] == $var['cate_id']) {
                        $str .= '<option value="' . $var2['cate_id'] . '">' . $var2['cate_name'] . '</option>' . "\n\r";
                    }
                }
            }
        }
        echo $str;
        die;
    }
    private function createCheck()
    {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['cate_name'] = htmlspecialchars($data['cate_name']);
        $data['rate'] = (int) $data['rate'];
        if (empty($data['cate_name'])) {
            $this->tuError('分类不能为空');
        }
        $data['orderby'] = (int) $data['orderby'];
		$data['photo'] = htmlspecialchars($data['photo']);
        return $data;
    }
    public function edit($cate_id = 0)
    {
        if ($cate_id = (int) $cate_id) {
            $obj = D('Tuancate');
            if (!($detail = $obj->find($cate_id))) {
                $this->tuError('请选择要编辑的抢购分类');
            }
            if ($this->isPost()) {
                $data = $this->editCheck();
                $data['cate_id'] = $cate_id;
                if (false !== $obj->save($data)) {
                    $obj->cleanCache();
                    $this->tuSuccess('操作成功', U('tuancate/index'));
                }
                $this->tuError('操作失败');
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->tuError('请选择要编辑的抢购分类');
        }
    }
    private function editCheck()
    {
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['cate_name'] = htmlspecialchars($data['cate_name']);
        $data['rate'] = (int) $data['rate'];
        if (empty($data['cate_name'])) {
            $this->tuError('分类不能为空');
        }
        $data['orderby'] = (int) $data['orderby'];
		$data['photo'] = htmlspecialchars($data['photo']);
        return $data;
    }
    public function delete($cate_id = 0)
    {
        if (is_numeric($cate_id) && ($cate_id = (int) $cate_id)) {
            $obj = D('Tuancate');
			if(false == $obj->check_parent_id($cate_id)){
				$this->tuError('当前分类下面还有二级分类');
			}
			if(false == $obj->check_cate_id_tuan($cate_id)){
				$this->tuError('当前分类下面还有商家');
			}
            $obj->delete($cate_id);
            $obj->cleanCache();
            $this->tuSuccess('删除成功', U('tuancate/index'));
        } else {
            $cate_id = $this->_post('cate_id', false);
            if (is_array($cate_id)) {
                $obj = D('Tuancate');
                foreach ($cate_id as $id) {
                    $obj->delete($id);
                }
                $obj->cleanCache();
                $this->tuSuccess('删除成功', U('tuancate/index'));
            }
            $this->tuError('请选择要删除的抢购分类');
        }
    }
    public function update()
    {
        $orderby = $this->_post('orderby', false);
        $obj = D('Tuancate');
        foreach ($orderby as $key => $val) {
            $data = array('cate_id' => (int) $key, 'orderby' => (int) $val);
            $obj->save($data);
        }
        $obj->cleanCache();
        $this->tuSuccess('更新成功', U('tuancate/index'));
    }
    public function hots($cate_id)
    {
        if ($cate_id = (int) $cate_id) {
            $obj = D('Tuancate');
            if (!($detail = $obj->find($cate_id))) {
                $this->tuError('请选择要编辑的抢购分类');
            }
            $detail['is_hot'] = $detail['is_hot'] == 0 ? 1 : 0;
            $obj->save(array('cate_id' => $cate_id, 'is_hot' => $detail['is_hot']));
            $obj->cleanCache();
            $this->tuSuccess('操作成功', U('tuancate/index'));
        } else {
            $this->tuError('请选择要编辑的抢购分类');
        }
    }
}