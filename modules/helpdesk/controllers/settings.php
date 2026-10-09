<?php
/**
 * @filesource modules/helpdesk/controllers/settings.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Settings;

use Gcms\Api as ApiController;
use Gcms\Config;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * GET  api/helpdesk/settings/get   อ่านค่ากำหนดของโมดูล
 * POST api/helpdesk/settings/save  บันทึกค่ากำหนด
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * อ่านค่ากำหนด
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function get(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }
            if (!ApiController::hasPermission($login, 'can_config')) {
                return $this->errorResponse('Permission required', 403);
            }

            return $this->successResponse([
                'data' => [
                    'helpdesk_first_status' => (int) self::$cfg->helpdesk_first_status,
                    'helpdesk_closed_status' => (int) self::$cfg->helpdesk_closed_status,
                    'helpdesk_reopened_status' => (int) self::$cfg->helpdesk_reopened_status,
                    'helpdesk_prefix' => (string) self::$cfg->helpdesk_prefix,
                    'helpdesk_no' => (string) self::$cfg->helpdesk_no,
                    'helpdesk_w' => (int) self::$cfg->helpdesk_w,
                    'helpdesk_sla_days' => (int) self::$cfg->helpdesk_sla_days,
                    'helpdesk_mail_user' => (string) self::$cfg->helpdesk_mail_user,
                    'helpdesk_mail_agent' => (string) self::$cfg->helpdesk_mail_agent,
                    'file_types' => implode(', ', self::$cfg->helpdesk_img_typies)
                ],
                'options' => [
                    'helpdesk_first_status' => \Helpdesk\Category\Model::toOptions('ticketstatus', false),
                    'helpdesk_closed_status' => \Helpdesk\Category\Model::toOptions('ticketstatus', false),
                    'helpdesk_reopened_status' => \Helpdesk\Category\Model::toOptions('ticketstatus', false)
                ]
            ], 'Settings retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * บันทึกค่ากำหนด
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function save(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }
            if (!ApiController::canModify($login, ['can_config'])) {
                return $this->errorResponse('Permission required', 403);
            }

            $statuses = \Helpdesk\Category\Model::map('ticketstatus', false);
            $errors = [];
            $first = $request->post('helpdesk_first_status')->toInt();
            $closed = $request->post('helpdesk_closed_status')->toInt();
            $reopened = $request->post('helpdesk_reopened_status')->toInt();
            foreach ([
                'helpdesk_first_status' => $first,
                'helpdesk_closed_status' => $closed,
                'helpdesk_reopened_status' => $reopened
            ] as $key => $value) {
                if (!isset($statuses[$value])) {
                    $errors[$key] = 'Please select';
                }
            }
            if (empty($errors) && $first === $closed) {
                $errors['helpdesk_closed_status'] = Language::get('The initial status and the closing status must not be the same');
            }
            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 400);
            }

            $config = Config::load(ROOT_PATH.'settings/config.php');
            $config->helpdesk_first_status = $first;
            $config->helpdesk_closed_status = $closed;
            // ของเดิมมีช่องนี้ในฟอร์มแต่ลืมบันทึก
            $config->helpdesk_reopened_status = $reopened;
            $config->helpdesk_prefix = $request->post('helpdesk_prefix')->topic();
            $config->helpdesk_no = $request->post('helpdesk_no')->topic();
            $config->helpdesk_w = max(100, $request->post('helpdesk_w')->toInt());
            $config->helpdesk_sla_days = max(0, min(365, $request->post('helpdesk_sla_days')->toInt()));
            $config->helpdesk_mail_user = $request->post('helpdesk_mail_user')->textarea();
            $config->helpdesk_mail_agent = $request->post('helpdesk_mail_agent')->textarea();

            if (!Config::save($config, ROOT_PATH.'settings/config.php')) {
                return $this->errorResponse(Language::replace('File %s cannot be created or is read-only.', 'settings/config.php'), 500);
            }

            \Index\Log\Model::add(0, 'helpdesk', 'Save', '{LNG_Module settings} {LNG_Helpdesk}', $login->id);

            return $this->redirectResponse('reload', 'Saved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
