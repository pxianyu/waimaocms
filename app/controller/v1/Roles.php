<?php
namespace app\controller\v1;
use app\BaseController;
use think\facade\Db;
use app\model\RolesContent;
// 引入jwt插件
use thans\jwt\facade\JWTAuth;
class Roles extends BaseController
{
    // 初始化
    protected function initialize()
    {

        parent::initialize();

        $payload = JWTAuth::auth(); //可验证token, 并获取token中的payload部分
        $this->admin_user = $payload['username']->getValue(); //可以继而获取payload里自定义的字段，比如uid
        $this->menu=Db::name('menu');
        $this->roles=Db::name('roles');
        $this->lang=request()->header('lang');
    }


    /* 获取菜单列表*/
    public function Menu_get_list()
    {
        $data = input('get.');
        $order='id desc';
        if($data['sort']!='')
        {
            switch ($data['sort'])
            {
                case 1;
                    $order='time desc';
                    break;
                case 2;
                    $order='weight desc';
                    break;
                case 3;
                    $order='weight asc';
                    break;
                default:
                    $order='id desc';
                    break;
            }

        }
        $where[]=['hidden','=',0];
        $list = $this->menu->where($where)->limit(100)->order($order)->select()->toArray();
        $total=$this->menu->where($where)->count();
        foreach($list as &$v)
        {
            $meta=json_decode($v['meta'],true);
            $v['title']=$v['label']=$meta['title'];
        }

        $mylist=list_to_tree($list);

        $d=[];
        $d['total']=$total;
        $d['items']=$mylist;
        return result($d, '读取列表成功', '200');

    }


    /* 添加 角色 */
    public function Create()
    {
        $data = input('post.');

        $data['time']=time();
        $data['admin_user']=$this->admin_user;

        $into = $this->roles->strict(false)->insertGetId($data);
        if ($into) {
            return result($into, '操作成功', '200');
        } else {

            return result('写入失败', '写入数据库失败，请联系管理员', '202');
        }

    }

    /* 读取 角色列表 */
    public function Roles_get_list()
    {
        $data = input('get.');

        if($data['limit']=='') $data['limit']='30';
        if($data['page']=='') $data['page']='0';
        $where=[];

        if($data['key']!='')
        {
            $where[]= ['title','like','%'.$data['key'].'%']; //查找下级
        }
        $order='id desc';
        if($data['sort']!='')
        {
            switch ($data['sort'])
            {
                case 1;
                    $order='time desc';
                    break;
                case 2;
                    $order='weight desc';
                    break;
                case 3;
                    $order='weight asc';
                    break;
                default:
                    $order='id desc';
                    break;
            }

        }

        $list = $this->roles->where($where)->limit($data['limit'])->page($data['page'])->order($order)->select()->toArray();
        $total=$this->roles->where($where)->count();
        foreach($list as $i=>$l)
        {
            $list[$i]['menu']=json_decode($list[$i]['menu'],true);
        }

        $d=[];
        $d['total']=$total;
        $d['items']=$list;
        return result($d, '读取列表成功', '200');
    }


    /* 删除 角色 */
    public function Create_delete()
    {
        $data = input('post.');
        $list=$data['data'];
        if(count($list)>0)
        {
            $RolesContent = new RolesContent();
           // $where[]= ['lang','=',$this->lang];;
            $where[]= ['id','in',$list];
            $del=$RolesContent->deleteAll($where);
            if($del)  return result($del, '删除成功，请稍后再试', '200');
            else  return result('更新失败，或者没有数据改变', '删除失败', '202');

        }else{
            return result('更新内容不能为空', '更新失败', '202');
        }

    }

    /* 编辑 角色 */
    public function Create_edit()
    {
        $data = input('post.');
        $list=$data['data'];
        if(count($list)>0)
        {
            $RolesContent  = new RolesContent();

            $up=$RolesContent->updateAll($list, $this->lang,$this->admin_user);
            if($up)  return result($up, '更新成功', '200');
            else  return result('更新失败，或者没有数据改变', '更新失败', '202');

        }else{
            return result('更新内容不能为空', '更新失败', '202');
        }

    }


}
