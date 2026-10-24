<?php
/**
 * @filesource modules/helpdesk/controllers/categories.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Categories;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Text;

/**
 * GET  api/helpdesk/categories/get   อ่านหมวดหมู่ / สถานะ / ความเร่งด่วน
 * POST api/helpdesk/categories/save  บันทึกทั้งชนิด
 *
 * แทน module=helpdesk-categories และ module=helpdesk-statuses ของระบบเดิม
 * ที่แยกเป็นสองหน้าคนละแบบ ทั้งที่เก็บข้อมูลในตารางเดียวกัน
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * อ่านรายการทั้งหมดของชนิดที่เลือก
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
            if (!ApiController::hasPermission($login, 'can_manage_helpdesk')) {
                return $this->errorResponse('Permission required', 403);
            }

            $type = \Helpdesk\Category\Model::validType($request->get('type')->filter('a-z_'));
            $datas = \Helpdesk\Category\Model::all($type, false);
            if (empty($datas)) {
                $datas = [
                    [
                        'category_id' => 1,
                        'topic' => '',
                        'color' => '#666666',
                        'is_active' => 1
                    ]
                ];
            }

            return $this->successResponse([
                'data' => [
                    'type' => $type,
                    'label' => \Helpdesk\Category\Model::typeLabel($type),
                    'options' => [
                        'columns' => \Helpdesk\Category\Model::getColumns($type),
                        'data' => $datas
                    ]
                ]
            ], 'Categories retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * บันทึกทั้งชนิด
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
            if (!ApiController::canModify($login, ['can_manage_helpdesk'])) {
                return $this->errorResponse('Permission required', 403);
            }

            $type = \Helpdesk\Category\Model::validType($request->post('type')->filter('a-z_'));
            $ids = $request->post('category_id', [])->topic();
            $topics = $request->post('topic', [])->topic();
            $colors = $request->post('color', [])->topic();
            $actives = $request->post('is_active', [])->toArray();

            $errors = [];
            $items = [];
            foreach ($ids as $key => $id) {
                $category_id = Text::topic($id);
                if ($category_id === '') {
                    continue;
                }
                if (isset($items[$category_id])) {
                    $errors['category_id_'.$key] = 'This :name already exist';
                    continue;
                }
                $topic = Text::topic(isset($topics[$key]) ? $topics[$key] : '');
                if ($topic === '') {
                    $errors['topic_'.$key] = 'Please fill in';
                    continue;
                }
                $items[$category_id] = [
                    'category_id' => $category_id,
                    'topic' => $topic,
                    // ชนิด category ไม่มีคอลัมน์สีในตาราง จึงเก็บเป็น null
                    'color' => $type === 'category' ? null : (isset($colors[$key]) ? $colors[$key] : ''),
                    'is_active' => !empty($actives[$key]) ? 1 : 0
                ];
            }

            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 400);
            }
            if (empty($items)) {
                return $this->errorResponse('Please fill in', 400);
            }

            \Helpdesk\Category\Model::save($type, $items);
            \Index\Log\Model::add(0, 'helpdesk', 'Save', ucfirst($type).' ('.count($items).' rows)', $login->id);

            return $this->redirectResponse('reload', 'Saved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
