<?php
/**
 * Wizard
 *
 * @link      https://aicode.cc/
 * @copyright 管宜尧 <mylxsw@aicode.cc>
 */

namespace App\Http\Controllers;


use App\Exceptions\ValidationException;
use App\Http\Controllers\Auth\UserActivateChannel;
use App\Repositories\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use UserActivateChannel;

    /**
     * 用户列表
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function users(Request $request)
    {
        return view('user.users', [
            'users' => User::orderBy('created_at', 'desc')->paginate(),
            'op'    => 'users',
        ]);
    }

    /**
     * 用户信息查看
     *
     * @param int $id
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function user($id)
    {
        return view('user.user', [
            'user' => User::where('id', $id)->firstOrFail(),
            'op'   => 'users',
        ]);
    }

    /**
     * 用户基本信息配置页面
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function basic(Request $request)
    {
        return view('user.basic', [
            'op'   => 'basic',
            'user' => \Auth::user(),
        ]);
    }

    /**
     * 修改用户基本信息
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function basicHandle(Request $request)
    {
        $uid = \Auth::user()->id;
        $this->validate(
            $request,
            [
                'username' => "required|string|max:255|username_unique:{$uid}",
            ]
        );

        $username = $request->input('username');

        \Auth::user()->update([
            'name' => $username,
        ]);

        $this->alertSuccess(__('common.operation_success'));

        return redirect(wzRoute('user:basic'));
    }

    /**
     * 管理员更新用户信息
     *
     * @param Request $request
     * @param         $id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateUser(Request $request, $id)
    {
        // 禁止在"用户管理"下更新自己的信息
        if ($id == \Auth::user()->id) {
            return redirect()->back();
        }

        $this->validate(
            $request,
            [
                'username' => "required|string|max:255|username_unique:{$id}",
                'role'     => 'required|in:1,2',
                'status'   => 'required|in:0,1,2',
            ]
        );

        $username = $request->input('username');
        $role     = $request->input('role');
        $status   = $request->input('status');

        $user         = User::where('id', $id)->firstOrFail();
        $user->name   = $username;
        $user->role   = $role;
        $user->status = $status;

        $user->save();

        $this->alertSuccess(__('common.operation_success'));

        return redirect()->back();
    }

    /**
     * 修改密码页面
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function password(Request $request)
    {
        return view('user.password', [
            'op' => 'password',
        ]);
    }

    /**
     * 修改密码
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function passwordHandle(Request $request)
    {
        $this->validate(
            $request,
            [
                'original_password' => 'required|user_password',
                'password'          => 'required|string|min:6|confirmed'
            ],
            [
                'original_password.required'      => __('passwords.validation.original_password_required'),
                'original_password.user_password' => __('passwords.validation.original_password_unmatch'),
                'password.required'               => __('passwords.validation.new_password_required'),
                'password.string'                 => __('passwords.validation.new_password_invalidate'),
                'password.min'                    => __('passwords.validation.new_password_at_least'),
                'password.confirmed'              => __('passwords.validation.new_password_confirm_failed'),
            ]
        );

        User::where('id', \Auth::user()->id)->update([
            'password' => \Hash::make($request->input('password'))
        ]);

        $this->alertSuccess(__('passwords.change_password_success'));
        return redirect(wzRoute('user:password'));
    }

    /**
     * 用户账户激活
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function activate(Request $request)
    {
        try {
            $token   = jwt_parse_token($request->input('token'));
            $user_id = $token->getClaim('uid');
            $email   = $token->getClaim('email');

            /** @var User $user */
            $user = User::findOrFail($user_id);
            if (!empty($user->email) && $user->email != $email) {
                abort(422, '激活链接中的邮箱地址与用户邮箱地址不匹配');
            }

            if ($user->isDisabled()) {
                abort(403, '用户账号已禁用，无法激活');
            }

            $user->status = User::STATUS_ACTIVATED;
            $user->save();

            $this->alertSuccess('账号激活成功');

        } catch (ValidationException $e) {
            abort(422, '很抱歉！此激活链接已失效');
        }
        return redirect('/');
    }

    /**
     * 发送激活邮件
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendActivateEmail(Request $request)
    {
        $user = \Auth::user();
        if ($user->isActivated() || $user->isDisabled()) {
            abort(422, '不符合发送激活邮件的条件');
        }

        $session                   = $request->session();
        $lastSendActivateEmailTime = $session->get('send_activate_email');
        // 15分钟内只允许发送一次激活邮件
        $retryDelay = 15 * 60;
        if ($lastSendActivateEmailTime && time() - $lastSendActivateEmailTime <= $retryDelay) {
            $this->alertError(sprintf(
                '请的操作太过频繁，请 %d 分钟后再试',
                (int)(($lastSendActivateEmailTime + $retryDelay - time()) / 60)
            ));
        } else {
            $session->put('send_activate_email', time());

            $this->sendUserActivateEmail($user);
            $this->alertSuccess('激活邮件发送成功');
        }

        return redirect()->back();
    }
}