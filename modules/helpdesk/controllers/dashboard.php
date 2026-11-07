<?php
/**
 * @filesource modules/helpdesk/controllers/dashboard.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Dashboard;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * GET api/helpdesk/dashboard/get    ตัวเลขสรุปของหน้าแรก
 * GET api/helpdesk/dashboard/graph  ข้อมูลกราฟ Ticket รายเดือน
 *
 * แทน Helpdesk\Home\Controller::addBlock ของระบบเดิม
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * การ์ดสรุป
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

            $statuses = \Helpdesk\Category\Model::map('ticketstatus', false);
            $canManage = ApiController::hasPermission($login, 'can_manage_helpdesk');
            $isAgent = ApiController::hasPermission($login, ['can_manage_helpdesk', 'helpdesk_agent']);

            // การ์ดของฉัน (ทุกคนเห็น)
            $mine = self::cards(Model::myCounts($login->id), $statuses, '/helpdesk');

            // การ์ดของเจ้าหน้าที่
            $agent = [];
            $overdue = 0;
            if ($canManage) {
                $agent = self::cards(Model::allCounts(), $statuses, '/helpdesk-jobs');
                $overdue = (int) Model::overdue();
            } elseif ($isAgent) {
                $agent = self::cards(Model::agentCounts($login->id), $statuses, '/helpdesk-jobs');
                $overdue = (int) Model::overdue($login->id);
            }
            if ($overdue > 0) {
                $agent[] = [
                    'status' => 0,
                    'topic' => 'Overdue',
                    'color' => '#FF0000',
                    'count' => $overdue,
                    'icon' => 'icon-warning',
                    'url' => '/helpdesk-jobs?overdue=1'
                ];
            }

            // เมนูลัด เปิด Ticket แยกตามหมวดหมู่ เหมือนระบบเดิม
            $shortcuts = [];
            foreach (\Helpdesk\Category\Model::all('category') as $item) {
                $shortcuts[] = [
                    'topic' => $item['topic'],
                    'url' => '/helpdesk-receive?id=0&category='.rawurlencode($item['category_id'])
                ];
            }

            return $this->successResponse([
                'data' => [
                    'is_agent' => $isAgent ? 1 : 0,
                    'can_manage' => $canManage ? 1 : 0,
                    'mine' => $mine,
                    'mine_total' => array_sum(array_column($mine, 'count')),
                    'agent' => $agent,
                    'agent_total' => array_sum(array_column($agent, 'count')),
                    'shortcuts' => $shortcuts
                ]
            ], 'Dashboard retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * ข้อมูลกราฟจำนวน Ticket รายเดือน
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function graph(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            // ชื่อชุดข้อมูลของกราฟถูกส่งเป็นข้อความสำเร็จรูป ฝั่ง JS ไม่แปลภาษาให้
            $this->initLanguage($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }
            if (!ApiController::hasPermission($login, ['can_manage_helpdesk', 'helpdesk_agent'])) {
                return $this->errorResponse('Permission required', 403);
            }

            return $this->successResponse(Model::monthly($request->get('months', 12)->toInt()), 'Graph data retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * แปลงจำนวน Ticket แยกตามสถานะ ให้เป็นรายการการ์ด
     * แสดงเฉพาะสถานะที่มีข้อมูล เหมือนระบบเดิม
     *
     * @param array $counts
     * @param array $statuses
     * @param string $url
     *
     * @return array
     */
    protected static function cards($counts, $statuses, $url)
    {
        $cards = [];
        foreach ($statuses as $status => $item) {
            if (empty($counts[$status])) {
                continue;
            }
            $cards[] = [
                'status' => $status,
                'topic' => $item['topic'],
                'color' => $item['color'],
                'count' => (int) $counts[$status],
                'icon' => 'icon-list',
                'url' => $url.'?status='.$status
            ];
        }

        return $cards;
    }
}
