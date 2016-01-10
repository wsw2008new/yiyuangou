<?php
	namespace AppMain\controller\Admin;
	use \System\BaseClass;
	/**
     * 物流类
     */
	class LogisticsController extends BaseClass {
        /**
         * 检查用户是否有填写收货地址
         */
        public function checkAddress(){
            $this->V(['user_id'=>['egNum',null,true]]);
            //获取订单id
            $id = intval($_POST['user_id']);
            $userAddr = $this->table('user_address')->where(['user_id'=>$id,'is_default'=>1])->get(['id'],true);
            if (!$userAddr) {
                $this->R('',70009);
            }
        }
		/**
	     * 添加物流单
	     */
		public function logisticsAdd(){
			$this->V(['bill_id'=>['egNum',null,true]]);
			//获取订单id
	        $billId = intval($_POST['bill_id']);

	        $this->V(['user_id'=>['egNum',null,true]]);
	        //获取用户id
	        $userId = intval($_POST['user_id']);

	        $rule = [
                    'logistics_number'  =>[],
                    'logistics_name'    =>[],
                ];
            $this->V($rule);
            foreach ($rule as $k => $v) {
            	$data[$k] = $_POST[$k];
            }
            $userAddr = $this->table('bill')->where(['id'=>$bill_id])->get(['address_id'],true);
            if (!$userAddr) {
                $userAddr['address_id'] =null;
            }
            $data = array(
            		'logistics_number'  =>$data['logistics_number'],
                    'logistics_name'    =>$data['logistics_name'],
                    'logistics_status'  =>0,
                    'bill_id'           =>$billId,
                    'user_address_id'   =>$userAddr['address_id'],
                    'add_time'          =>time(),
            	);
            $logistics = $this->table('logistics')->save($data);
            if (!$logistics) {
            	$this->R('',40001);
            }
            //设置bill表is_post状态为1
            $billPost = $this->table('bill')->where(['id'=>$billId])->update(['is_post'=>1,'status'=>2]);
            if (!$billPost) {
                $this->R('',40001);
            }
            $this->R();
		}

		/**
	     * 修改物流单
	     */
		public function logisticsEdit(){
			$this->V(['id'=>['egNum',null,true]]);
	        $logisticsId = intval($_POST['id']);
	        $rule = [
	        		'id'                =>[],
                	'logistics_number'  =>[],
                    'logistics_name'    =>[],
                    //'logistics_status'  =>[],
            ];
            $this->V($rule);
            $logistics = $this->table('logistics')->where(['id'=>$logisticsId,'is_on'=>1])->get(['id'],true);
            if(!$logistics){
                $this->R('',70009);
            }

            unset($rule['id']);
            foreach ($rule as $k=>$v){
                if(isset($_POST[$k])){
                    $data[$k] = $_POST[$k];
                }
            }

            $logistics = $this->table('logistics')->where(['id'=>$logisticsId])->update($data);
            if(!$logistics){
                $this->R('',40001);
            }
            $this->R();
		}

		/**
         * 物流列表
         */
        public function logisticsList(){
            $where=['is_on'=>1];
            $pageInfo = $this->P();
            $class = $this->table('logistics')->where($where)->order('add_time desc');
            //查询并分页
            $logisticslist = $this->getOnePageData($pageInfo,$class,'get','getListLength',null,false);
            if($logisticslist){
                foreach ($logisticslist as $k=>$v){
                    $logisticslist[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
                    $logisticslist[$k]['update_time'] = date('Y-m-d H:i:s',$v['update_time']);
                    $id = $v['logistics_number'];
                    $express = new \System\lib\Express\Express();
                    $expressdetail = $express->getorder($id);
                    if (@$expressdetail['state'] == null) {
                        @$expressdetail['state'] = "0";
                    }else{
                    $updateExpress = $this->table('logistics')->where(['is_on'=>1,'logistics_number'=>$id])->update(['logistics_status'=>$expressdetail['state']]);
                    if (!$updateExpress) {
                        $this->R('',70009);
                    }
                    }
                }
            }else{
                $logisticslist = null;
            }
            $this->R(['logisticslist'=>$logisticslist,'pageInfo'=>$pageInfo]);
        }

        /**
         * 查询一条物流信息
         */
        public function logisticsOneDetail(){
            $this->V(['logistics_id'=>['egNum',null,true]]);
	        $logisticsId = intval($_POST['logistics_id']);
                //查询一条数据
                $logistics = $this->table('logistics')->where(['is_on'=>1,'id'=>$logisticsId])->get(null,true);
                if(!$logistics){
                    $this->R('',70009);
                }
                //查询一条数据
                
                $logistics['update_time'] = date('Y-m-d H:i:s',$logistics['update_time']);
                $logistics['add_time'] = date('Y-m-d H:i:s',$logistics['add_time']);
                $user_address = $this->table('user_address')->where(['is_on'=>1,'id'=>$logistics['user_address_id']])->get(['user_id','province','city','area','street','mobile','name'],true);
                if ($user_address) {
                    $logistics = array_merge($logistics, $user_address);
                }
                //$logistics = array_merge($logistics, $user_address);

            $this->R(['logistics'=>$logistics]);
        }

        /**
         *删除一条物流数据（设置数据库字段为0，相当于回收站）
         */
        public function logisticsDelete(){
        
            $this->V(['logistics_id'=>['egNum',null,true]]);
            $id = intval($_POST['logistics_id']);
             
            $logistics = $this->table('logistics')->where(['id'=>$id,'is_on'=>1])->get(['id'],true);
        
            if(!$logistics){
                $this->R('',70009);
            }
        
            $logistics = $this->table('logistics')->where(['id'=>$id])->update(['is_on'=>0]);
            if(!$logistics){
                $this->R('',40001);
            }
            $this->R();
        }
        
        /**
         *删除一条物流数据（清除数据）
         */
        public function logisticsDeleteconfirm(){
            $this->V(['logistics_id'=>['egNum',null,true]]);
            $id = intval($_POST['logistics_id']);
             
            $logistics = $this->table('logistics')->where(['id'=>$id,'is_on'=>1])->get(['id'],true);
            if(!$logistics){
                $this->R('',70009);
            }
            $logistics = $this->table('logistics')->where(['id'=>$id])->delete();
            if(!$logistics){
                $this->R('',40001);
            }
            $this->R();
        }
        public function updateExpress(){
        $this->V(['logistics_number'=>['egNum',null,true]]);
        $id = $_POST['logistics_number'];
        $express = new \System\lib\Express\Express();
        $expressdetail = $express->getorder($id);
        $updateExpress = $this->table('logistics')->where(['logistics_number'=>$id])->update(['logistics_status'=>$expressdetail['state']]);
    }
	}
?>