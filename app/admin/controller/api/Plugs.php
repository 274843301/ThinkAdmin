<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2020 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
// | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

namespace app\admin\controller\api;

use think\admin\Controller;
use think\admin\service\AdminService;
use think\admin\service\SystemService;
use think\exception\HttpResponseException;

/**
 * 通用插件管理
 * Class Plugs
 * @package app\admin\controller\api
 */
class Plugs extends Controller
{

    /**
     * 系统图标选择器
     */
    public function icon()
    {
        $this->title = '图标选择器';
        $this->field = $this->app->request->get('field', 'icon');
        $this->fetch(realpath(__DIR__ . '/../../view/api/icon.html'));
    }


    /**
     * 当前运行模式
     * @login true
     */
    public function debug()
    {
        if (AdminService::instance()->isSuper()) if (input('state')) {
            SystemService::instance()->setRuntime('product');
            sysoplog('系统运维管理', '由开发模式切换为产品模式');
            $this->success('已切换为产品模式！');
        } else {
            SystemService::instance()->setRuntime('debug');
            sysoplog('系统运维管理', '由产品模式切换为开发模式');
            $this->success('已切换为开发模式！');
        } else {
            $this->error('只有超级管理员才能操作！');
        }
    }

    /**
     * 检查任务状态
     * @login true
     */
    public function queue()
    {
        if (AdminService::instance()->isSuper()) try {
            $message = $this->app->console->call('xadmin:queue', ['status'])->fetch();
            if (preg_match('/process.*?\d+.*?running/', $message, $attrs)) {
                echo '<span class="color-green">' . $message . '</span>';
            } else {
                echo '<span class="color-red">' . $message . '</span>';
            }
        } catch (\Exception $exception) {
            echo '<span class="color-red">' . $exception->getMessage() . '</span>';
        } else {
            echo '<span class="color-red">只有超级管理员才能操作！</span>';
        }
    }

    /**
     * 优化数据库
     * @login true
     */
    public function optimize()
    {
        if (AdminService::instance()->isSuper()) {
            sysoplog('系统运维管理', '创建数据库优化任务');
            $this->_queue('优化数据库所有数据表', 'xadmin:database optimize', 0, [], 0, 0);
        } else {
            $this->error('只有超级管理员才能操作！');
        }
    }

    /**
     * 清理系统配置
     * @login true
     */
    public function clearConfig()
    {
        if (AdminService::instance()->isSuper()) try {
            $this->app->db->transaction(function () {
                [$tmpdata, $alldata] = [[], []];
                foreach ($this->app->db->name('SystemConfig')->cursor() as $item) {
                    $tmpdata[$item['type']][$item['name']] = $item['value'];
                    ksort($tmpdata[$item['type']]);
                }
                ksort($tmpdata);
                foreach ($tmpdata as $type => $items) foreach ($items as $name => $value) {
                    $alldata[] = ['type' => $type, 'name' => $name, 'value' => $value];
                }
                $this->app->db->name('SystemConfig')->whereRaw('1=1')->delete();
                $this->app->db->name('SystemConfig')->insertAll($alldata);
            });
            $this->app->cache->delete('SystemConfig');
            $GLOBALS['oplogs'] = [];
            sysoplog('系统运维管理', '清理系统配置成功');
            $this->success('清理系统配置成功！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        } else {
            $this->error('只有超级管理员才能操作！');
        }
    }

    /**
     * 网站压缩发布
     * @login true
     */
    public function pushRuntime()
    {
        if (AdminService::instance()->isSuper()) try {
            AdminService::instance()->clearCache();
            SystemService::instance()->pushRuntime();
            sysoplog('系统运维管理', '网站缓存加速成功');
            $this->success('网站缓存加速成功！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        } else {
            $this->error('只有超级管理员才能操作！');
        }
    }

    /**
     * 清理运行缓存
     * @login true
     */
    public function clearRuntime()
    {
        if (AdminService::instance()->isSuper()) try {
            AdminService::instance()->clearCache();
            SystemService::instance()->clearRuntime();
            sysoplog('系统运维管理', '清理网站缓存日志');
            $this->success('清理网站缓存成功！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        } else {
            $this->error('只有超级管理员才能操作！');
        }
    }
}