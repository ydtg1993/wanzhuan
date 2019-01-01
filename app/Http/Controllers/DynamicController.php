<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/4
 * Time: 10:30
 */

namespace App\Http\Controllers;


use App\Models\Dynamic;
use App\Models\DynamicComment;
use App\Models\DynamicPicture;
use App\Models\DynamicRemind;
use App\Models\DynamicReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DynamicController extends Controller
{
    /**
     * 动态列表
     *
     * 一次加载3条动态
     */
    public function list(Request $request)
    {
        $limit = 3;
        $skip = ($request->input('page', 1) - 1) * $limit;
        $dynamics = Dynamic::with('pictures', 'like')
            ->where('user_id', $request->master_id)
            ->orderBy('created_at', 'DESC')
            ->skip($skip)
            ->take($limit)
            ->get();
        return $this->success($dynamics);
    }

    /**
     * 动态详情
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request)
    {
        $dycnamic = Dynamic::with([
            'pictures',
        ])->find($request->dynamic_id);
        if (!$dycnamic) {
            return $this->error('动态不存在');
        }
        return $this->success($dycnamic);
    }

    /**
     * 发布新动态
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function publish(Request $request)
    {
        $roles = [
            'content' => 'required|string|max:400',
            'pictures' => 'required|array|max:9'
        ];
        $this->validate($request, $roles);
        $pictures = $request->input('pictures');

        try {
            DB::beginTransaction();
            foreach ($pictures as $picture) {
                $pictureData[] = new DynamicPicture([
                    'url' => $picture['url'],
                    'sort' => $picture['sort']
                ]);
            }
            $dynamic = app('auth')->user()->dynamics()->create($request->only('content'));

            if (!$dynamic) {
                throw new \Exception();
            }
            $dynamic->pictures()->saveMany($pictureData);
            DB::commit();
            return $this->success('发表成功');
        } catch (\Exception $e) {
            //return $this->error($e->getMessage());
            DB::rollBack();
            return $this->error($e->getMessage());//'发表失败');
        }
    }

    /**
     * 发表评论
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function commentsPublish(Request $request)
    {
        $roles = [
            'from_id' => 'required|integer',
            'to_id' => 'present|integer',
            'content' => 'required|string|max:140',
            'type' => 'required|in:dynamic,comments',
        ];
        $this->validate($request, $roles);
        try {
            DB::beginTransaction();

            $dynamic = Dynamic::find($request->dynamic_id);
            if (!$dynamic) {
                return $this->error('动态不存在');
            }

            $data = $request->only('from_id', 'to_id', 'content', 'type');
            if (!$dynamic->comments()->create($data)) {
                return $this->error('增加失败');
            }
            $type = array_get($data, 'type') == 'dynamic' ? 1 : 2;
            info($data);
            if (!$this->addNotify($dynamic, $data, $type)) {
                return $this->error('通知失败');
            }
            $dynamic->increment('comments_num', 1);
            DB::commit();
            return $this->success('成功');
        } catch (\Exception $e) {
            DB::rollBack();
            info($e->getMessage());
            return $this->error($e->getMessage()/*'出错了'*/);
        }
    }

    /**
     * 根据动态ID获取评论列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function commentsList(Request $request)
    {
        $limit = $request->input('limit', 5);
        $page = $request->input('page', 1);
        $skip = ($page - 1) * $limit;

        $comments = DynamicComment::with([
            'fromUser' => function ($query) {
                $query->select('id', 'nickname', 'avatar', 'sexy', 'birth');
            },
            'toUser' => function ($query) {
                $query->select('id', 'nickname', 'avatar', 'sexy', 'birth');
            },
            'like'
        ])
            ->where('dynamic_id', $request->dynamic_id)
            ->orderBy('created_at', 'desc')
            ->skip($skip)
            ->take($limit)
            ->get();
        return $this->success($comments);
    }

    /**
     * 删除动态
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function del(Request $request)
    {
        if (Dynamic::destroy($request->dynamic_id)) {
            return $this->success('删除成功');
        }
        return $this->error('删除失败');
    }

    /**
     * 删除评论
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function commentsDel(Request $request)
    {
        try {
            DB::beginTransaction();
            $dynamicComment = DynamicComment::find($request->input('id', 0));

            if (!$dynamicComment) {
                return $this->error('数据不存在');
            }

            if ($dynamicComment->from_id != app('auth')->id()) {
                return $this->error('只能删除自己的评论');
            }

            $dynamicComment->delete();
            $data = [
                'dynamic_id' => $dynamicComment->dynamic_id,
                'from_id' => $dynamicComment->from_id,
                'message' => $dynamicComment->content
            ];
            //清除提醒
            DynamicRemind::where($data)->delete();
            Dynamic::where('id', $dynamicComment->dynamic_id)->decrement('comments_num', 1);
            DB::commit();
            return $this->success('删除成功');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('删除失败');
        }
    }

    /**
     * 点赞
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function praise(Request $request)
    {
        $roles = [
            'id' => 'required|integer', //点赞对象ID
            'from_id' => 'required|integer',
            'to_id' => 'present|integer',
            'type' => 'required|in:comments,dynamic',
        ];
        $this->validate($request, $roles);

        try {
            DB::beginTransaction();
            if ($request->type == 'comments') {
                $model = new DynamicComment();
                $table = 'dynamic_comments_praises';
                $praiseData = [
                    'dynamic_comments_id' => $request->id,
                    'user_id' => $request->from_id
                ];
            } else {
                $model = new Dynamic();
                $table = 'dynamic_praises';
                $praiseData = [
                    'dynamic_id' => $request->id,
                    'user_id' => $request->from_id
                ];
            }

            if (DB::table($table)->where($praiseData)->first()) {
                return $this->error('不能重复点赞');
            }

            DB::table($table)->insert($praiseData);

            $model = $model->find($request->id);

            if (!$model->increment('praise', 1)) {
                return $this->error('操作失败');
            }

            $dynamic = $model instanceof Dynamic ? $model : $model->dynamic;

            if (!$this->addNotify($dynamic, $request->all(), 0)) {
                return $this->error('操作失败');
            }
            DB::commit();
            return $this->success('点赞成功');
        } catch (\Exception $e) {
            DB::rollBack();
            info($e->getMessage());
            return $this->error($e->getMessage());
        }

    }

    /**
     * 拉取提醒
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function remind(Request $request)
    {
        try {
            $reminds = DynamicRemind::where('to_id', app('auth')->id());
            $reminds1 = $reminds;
            if ($request->has('count')) {
                $result = ['num' => $reminds->count()];
            } else {
                $limit = $request->input('limit', 10);
                $page = $request->input('page', 1);
                $skip = ($page - 1) * $limit;
                $result = $reminds->with([
                    'fromUser' => function ($query) {
                        $query->select('id', 'nickname', 'avatar', 'sexy', 'birth');
                    },
                    'toUser' => function ($query) {
                        $query->select('id', 'nickname', 'avatar', 'sexy', 'birth');
                    },
                    'dynamic.pictures'
                ])->orderBy('created_at', 'desc')
                    ->skip($skip)
                    ->take($limit)
                    ->get();
                $reminds1->delete();
            }
            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error('获取失败');
        }

    }

    /**
     * 清除提醒作废
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    /*public function removeRemind(Request $request)
    {
        if (DynamicRemind::where('to_id', app('auth')->id())->delete()) {
            return $this->success('清除成功');
        }
        return $this->error('清除失败');
    }*/

    /**
     * 举报
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function reports(Request $request)
    {
        $roles = [
            'user_id' => 'required|integer',
            'report_id' => 'required|integer',
            'type' => 'required|in:comments,dynamic',
            'describe' => 'present|string'
        ];
        $this->validate($request, $roles);
        if (DynamicReport::create($request->all())) {
            return $this->success('举报成功');
        }
        return $this->error('举报失败');
    }

    /**
     * 添加通知
     *
     * @param $dynamic
     * @param $form
     * @param $to
     * @param $type
     * @return bool
     */
    protected function addNotify($dynamic, $data, $type)
    {
        /**
         * 自己给自己评论 不通知
         * 评论者自己给自己评论   不通知
         * 给主人评论    主人收到
         * 其他评论者给评论者评论 评论者收到通知
         * 主人给评论者评论 评论者收到通知
         *
         * 对象不是自己就需要通知,不区分是评论还是点赞
         */
        //$from, $to, $type,$message = ''
        $from = array_get($data, 'from_id');
        $to = array_get($data, 'to_id');
        $message = array_get($data, 'content', '');

        try {
            $noticeData = [];
            if ($to != $from) {
                $data = [
                    'dynamic_id' => $dynamic->id,
                    'from_id' => $from,
                    'type' => $type,
                    'message' => $message,
                    'created_at' => Carbon::now()
                ];
                /**
                 * 需要通知主人
                 */
                if (!$type && $to != $dynamic->user_id && $dynamic->user_id != $from) {
                    $data['to_id'] = $dynamic->user_id;
                    $noticeData[] = $data;
                }
                if ($to) {
                    $data['to_id'] = $to;
                    $noticeData[] = $data;
                }
                DB::table('dynamic_remind')->insert($noticeData);
            }
            return true;
        } catch (\Exception $e) {
            info($e->getMessage());
            return false;
        }
    }

}