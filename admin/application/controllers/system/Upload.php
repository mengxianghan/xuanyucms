<?php
/**
 * author    mengxianghan
 * links     http://www.xuanyunet.com
 * date      2019/7/25
 */

use Ramsey\Uuid\Uuid;

class Upload extends MY_Controller
{
    protected $pattern = array('/\\\/', '/\/\//');
    protected $replacement = '/';

    /**
     * 获取列表
     */
    public function get_list()
    {
        try {
            $upload_dir_id = $this->input->get('upload_dir_id');
            $orig_name = $this->input->get('orig_name');
            $id = $this->input->get('id');
            $where = array();
            $like = array();
            if ($upload_dir_id != '') {
                $where['upload_dir_id'] = explode(',', $upload_dir_id);
            }
            if ($id != '') {
                $where['id'] = explode(',', $id);
            }
            if ($orig_name) {
                $like['orig_name'] = $orig_name;
            }
            $result = $this->common->get_list(array(
                'table' => 'upload',
                'where' => $where,
                'like' => $like,
                'order_by' => 'create_time desc'
            ));
            return ajax(EXIT_SUCCESS, null, $result);
        } catch (Exception $e) {
            return ajax(EXIT_ERROR, $e->getMessage());
        }
    }

    /**
     * 上传
     */
    public function do_upload()
    {
        try {
            //权限验证
            //$this->permission->validate($this->config->item('auth_upload'));
            $upload_dir = $this->input->post('upload_dir');
            $allowed_file_type = $this->input->post('allowed_file_type');
            $allowed_file_size = $this->input->post('allowed_file_size');
            //相对
            $relative_path = $upload_dir;
            //上传路径
            $upload_path = preg_replace($this->pattern, $this->replacement, "../{$relative_path}/");
            $config['upload_path'] = $upload_path;
            //允许上传的文件类型
            $config['allowed_types'] = $allowed_file_type;
            //将文件后缀转换成小写
            $config['file_ext_tolower'] = true;
            //将文件名替换成随机字符
            $config['encrypt_name'] = true;
            //允许上传文件大小的最大值
            $config['max_size'] = (int)$allowed_file_size * 1024;

            //检查上传目录是否存在，不存在时创建
            if (!is_dir($upload_path)) {
                mkdir(iconv('UTF-8', 'GBK', $upload_path), 0777, true);
            }
            $this->load->library('upload', $config);

            //上传出错
            if (!$this->upload->do_upload('file')) {
                $error = $this->upload->display_errors('', '');
                throw new Exception($error);
            }
            $data = $this->upload->data();
            $upload_dir_id = $this->input->post('upload_dir_id');
            //写入数据库
            $values = array(
                'id' => Uuid::uuid4(),
                'upload_dir_id' => $upload_dir_id,
                'file_name' => $data['file_name'],
                'file_type' => $data['file_type'],
                'file_path' => $data['file_path'],
                'full_path' => $data['full_path'],
                'raw_name' => $data['raw_name'],
                'orig_name' => $data['orig_name'],
                'client_name' => $data['client_name'],
                'file_ext' => $data['file_ext'],
                'file_size' => (int)$data['file_size'] * 1024,
                'is_image' => $data['is_image'] ? '1' : '0',
                'image_width' => $data['image_width'],
                'image_height' => $data['image_height'],
                'image_type' => $data['image_type'],
                'image_size_str' => $data['image_size_str'],
                'relative_path' => preg_replace($this->pattern, $this->replacement, "{$relative_path}/{$data['file_name']}")
            );
            $result = $this->common->insert('upload', $values);
            $res = array_merge($values, array('id' => $result['insert_id']));
            return ajax(EXIT_SUCCESS, null, $res);
        } catch (Exception $e) {
            return ajax(EXIT_ERROR, $e->getMessage());
        }
    }

    /**
     * 删除文件
     */
    public function delete()
    {
        try {
            $id = $this->input->post('id');
            if ($id == '') {
                throw new Exception('参数不完整');
            }
            $data = $this->common->get_data(array(
                'table' => 'upload',
                'where' => array('id' => $id)
            ));
            //删除数据
            $result = $this->common->delete('upload', array('id' => $id));
            //删除文件
            unlink($data['full_path']);
            return ajax(EXIT_SUCCESS, null, $result);
        } catch (Exception $e) {
            return ajax(EXIT_ERROR, $e->getMessage());
        }
    }

}