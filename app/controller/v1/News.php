<?php
namespace app\controller\v1;
use app\BaseController;
use think\facade\Db;
use app\model\AttributeContent;
use app\model\NewsClassifyContent;
use app\model\NewsContent;
// 引入jwt插件
use thans\jwt\facade\JWTAuth;
class News extends BaseController
{
    // 初始化
    protected function initialize()
    {

        parent::initialize();
        $this->news_attribute=Db::name('news_attribute');
        $this->news_product=Db::name('news');
        $this->classify=Db::name('news_classify');
        $this->lang=request()->header('lang');
        $payload = JWTAuth::auth(); //可验证token, 并获取token中的payload部分
        $this->admin_user= $payload['username']->getValue(); //可以继而获取payload里自定义的字段，比如uid

        /*验证用户真实性*/
        $this->admin=Db::name('admin')->field('department_rules,btn_rules,roles,id')->where('username', $this->admin_user)->find();
        if(!$this->admin)  return result('操作失败，用户不存在，请重新登录', '用户校验失败', '201');

    }



    /* 添加 新闻属性 */
    public function Create_attribute()
    {
        $data = input('post.');
        $data['lang']=$this->lang;
        $data['time']=time();
        $data['admin_user']=$this->admin_user;


        $data['shuxing']=json_encode( $data['shuxing'],JSON_UNESCAPED_UNICODE);
        $into = $this->news_attribute->strict(false)->insertGetId($data);
        if ($into) {
            return result($into, '操作成功', '200');
        } else {

            return result('写入失败', '写入数据库失败，请联系管理员', '202');
        }

    }



    /* 读取 新闻属性列表 */
    public function Create_attribute_list()
    {
        $data = input('get.');

        if($data['limit']=='') $data['limit']='30';
        if($data['page']=='') $data['page']='0';
        $where=[];

        $order='weight asc,id desc';
        if($data['key']!='')
        {
            $where[]= ['name','like','%'.$data['key'].'%'];
        }



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
                    $order='weight asc,id desc';
                    break;
            }

        }



        $list = $this->news_attribute->where('lang',$this->lang)->where($where)->limit($data['limit'])->page($data['page'])->order($order)->select()->toArray();
        $total=$this->news_attribute->where('lang',$this->lang)->where($where)->count();

        foreach($list as $i=>$info) {

            if( $list[$i]['shuxing']=='')  $list[$i]['shuxing']=[];
            else{
                $list[$i]['shuxing']=json_decode( $list[$i]['shuxing'],true);
                foreach($list[$i]['shuxing'] as $ii=>$s)
                {
                    $list[$i]['shuxing'][$ii]['value_array']=explode(PHP_EOL,$s['value']);

                }

            }
        }

        $d=[];
        $d['total']=$total;
        $d['items']=$list;
        return result($d, '读取列表成功', '200');
    }


    /* 编辑 新闻属性 */
    public function Create_attribute_edit()
    {
        $data = input('post.');
        $list=$data['data'];
        if(count($list)>0)
        {


            $AttributeContent  = new AttributeContent();
            $payload = JWTAuth::auth(); //可验证token, 并获取token中的payload部分
            $this->admin_user= $payload['username']->getValue(); //可以继而获取payload里自定义的字段，比如uid
            $up=$AttributeContent->updateAll($list, $this->lang,$this->admin_user);
            if($up)  return result($up, '更新成功', '200');
            else  return result('更新失败，或者没有数据改变', '更新失败', '202');

        }else{
            return result('更新内容不能为空', '更新失败', '202');
        }

    }

    /* 删除 新闻属性 */
    public function Create_attribute_delete()
    {
        $data = input('post.');
        $list=$data['data'];
        if(count($list)>0)
        {
            $AttributeContent  = new AttributeContent();
            $where[]= ['lang','=',$this->lang];;
            $where[]= ['id','in',$list];
            $del=$AttributeContent->deleteAll($where);
            if($del)  return result($del, '删除成功，请稍后再试', '200');
            else  return result('更新失败，或者没有数据改变', '删除失败', '202');

        }else{
            return result('更新内容不能为空', '更新失败', '202');
        }

    }



    /* 添加 新闻分类 */
    public function CreateClassify()
    {
        $data = input('post.');
        $data['lang']=$this->lang;
        $data['time']=time();
        $data['admin_user']=$this->admin_user;
        $into = $this->classify->strict(false)->insertGetId($data);
        if ($into) {
            return result($into, '操作成功', '200');
        } else {

            return result('写入失败', '写入数据库失败，请联系管理员', '202');
        }

    }

    /* 读取 产品分类 */
    public function CreateClassify_get()
    {
        $data = input('post.');

        $info = $this->classify->where('lang',$this->lang)->where('id',intval($data['id']))->find();
        if ($info) {
            return result($info, '读取成功', '200');
        } else {

            return result('获取分类失败，分类不存在', '写入数据库失败，请联系管理员', '202');
        }

    }

    /* 读取新闻分类列表 */
    public function CreateClassify_get_list()
    {
        $data = input('get.');

        if($data['limit']=='') $data['limit']='30';
        if($data['page']=='') $data['page']='0';
        $where=[];
        if($data['parent']>='0')
        {
            $where[]= ['parent','=',intval($data['parent'])]; //查找下级
        }
        if($data['key']!='')
        {
            $where[]= ['title','like','%'.$data['key'].'%']; //查找下级
        }
        $order='weight asc,id desc';
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
                    $order='weight asc,id desc';
                    break;
            }

        }

        $list = $this->classify->where('lang',$this->lang)->where($where)->field('title as label,id as value,id,parent,title,weight,admin_user,time')->limit($data['limit'])->page($data['page'])->order($order)->select()->toArray();
        $total=$this->classify->where('lang',$this->lang)->where($where)->count();
        $listt=list_to_tree($list);

        $d=[];
        $d['total']=$total;
        $d['items']=$listt;
        return result($d, '读取列表成功', '200');
    }

    /* 编辑 新闻分类 */
    public function CreateClassify_edit()
    {
        $data = input('post.');
        $list=$data['data'];
        if(count($list)>0)
        {
            $NewsClassifyContent  = new NewsClassifyContent();

            $up=$NewsClassifyContent->updateAll($list, $this->lang,$this->admin_user);
            if($up)  return result($up, '更新成功', '200');
            else  return result('更新失败，或者没有数据改变', '更新失败', '202');

        }else{
            return result('更新内容不能为空', '更新失败', '202');
        }

    }

    /* 删除 新闻分类 */
    public function CreateClassify_delete()
    {
        $data = input('post.');
        $list=$data['data'];
        if(count($list)>0)
        {
            $NewsClassifyContent= new NewsClassifyContent();
            $where[]= ['lang','=',$this->lang];;
            $where[]= ['id','in',$list];
            $del=$NewsClassifyContent->deleteAll($where);
            if($del)  return result($del, '删除成功，请稍后再试', '200');
            else  return result('更新失败，或者没有数据改变', '删除失败', '202');

        }else{
            return result('更新内容不能为空', '更新失败', '202');
        }

    }

    /* 添加 新闻 */
    public function Create_product()
    {
        $data = input('post.');
        $data['lang']=$this->lang;
        $data['time']=time();
        $data['admin_user']=$this->admin_user;
        $data['image']=json_encode($data['image']);
        $data['attvalue']=json_encode($data['attvalue'],JSON_UNESCAPED_UNICODE);
        $data['attvaluebox']=json_encode($data['attvaluebox'],JSON_UNESCAPED_UNICODE);
        $data['attvaluetext']=json_encode($data['attvaluetext'],JSON_UNESCAPED_UNICODE);
        $data['attvaluetextarea']=json_encode($data['attvaluetextarea'],JSON_UNESCAPED_UNICODE);
        if(isset($data['attvalueimage'])) $data['attvalueimage']=json_encode($data['attvalueimage'],JSON_UNESCAPED_UNICODE);


        if($this->admin['department_rules']!='')
        {
            $data['department_rules']=$this->admin['department_rules'];
        }
        $into = $this->news_product->strict(false)->insertGetId($data);
        if ($into) {
            return result($into, '操作成功', '200');
        } else {

            return result('写入失败', '写入数据库失败，请联系管理员', '202');
        }

    }


    /* 读取 新闻列表 */
    public function Create_product_list()
    {
        $data = input('get.');

        if($data['limit']=='') $data['limit']='30';
        if($data['page']=='') $data['page']='0';
        $where=[];

        $order='weight asc,id desc';
        if($data['key']!='')
        {
            $where[]= ['title','like','%'.$data['key'].'%'];
        }



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
                    $order='weight asc,id desc';
                    break;
            }

        }



        $list = $this->news_product->where('lang',$this->lang)->where($where)->limit($data['limit'])->page($data['page'])->order($order)->select()->toArray();
        $total=$this->news_product->where('lang',$this->lang)->where($where)->count();

        foreach($list as $i=>$info) {

            /* 处理图片 */
            $list[$i]['image']=json_decode( $list[$i]['image'],true);
            if(isset( $list[$i]['image'][0]))
            {
                $list[$i]['image_array']=[];
                $list[$i]['litpic']=image_url('uploads/'.$list[$i]['image'][0]['name']);
                foreach( $list[$i]['image'] as $v)
                {
                    $list[$i]['image_array'][]=image_url('uploads/'.$list[$i]['image'][0]['name']);
                }
            }
            else{
                $list[$i]['litpic']=image_url('/uploads/default/user.png');
            }
            foreach( $list[$i]['image'] as $ii=>$j)
            {
                $list[$i]['image'][$ii]['url']=image_url('/uploads/'.$list[$i]['image'][$ii]['name']);;
            }
            /* 处理图片 */

            /* 处理分类*/
            $classify=json_decode($info['parent'],true);
            if(is_array($classify))
            {
                $classify=array_filter(array_values($classify));
                $list[$i]['classify']=Db::name('news_classify')->field('id,title')->where('id','in',$classify)->select()->toArray();
            }
            else{
                $list[$i]['classify']=[];
            }
            /* 处理分类*/
            $list[$i]['parent']=json_decode( $list[$i]['parent'],true);

        }

        $d=[];
        $d['total']=$total;
        $d['items']=$list;
        return result($d, '读取列表成功', '200');
    }

    /* 编辑新闻 */
    public function CreateProduct_edit()
    {
        $data = input('post.');
        $list=$data['data'];
        if(count($list)>0)
        {
            $NewsContent  = new NewsContent();

            $up=$NewsContent->updateAll($list, $this->lang,$this->admin_user);
            if($up)  return result($up, '更新成功', '200');
            else  return result('更新失败，或者没有数据改变', '更新失败', '202');

        }else{
            return result('更新内容不能为空', '更新失败', '202');
        }

    }


    /* 删除 新闻分类 */
    public function CreateProduct_delete()
    {
        $data = input('post.');
        $list=$data['data'];
        if(count($list)>0)
        {
            $NewsContent  = new NewsContent();
            $where[]= ['lang','=',$this->lang];;
            $where[]= ['id','in',$list];
            $del=$NewsContent->deleteAll($where);
            if($del)  return result($del, '删除成功，请稍后再试', '200');
            else  return result('更新失败，或者没有数据改变', '删除失败', '202');

        }else{
            return result('更新内容不能为空', '更新失败', '202');
        }

    }



}
