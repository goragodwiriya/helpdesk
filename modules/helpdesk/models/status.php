<?php
/**
 * @filesource modules/helpdesk/modules/status.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Status;

use Kotchasan\Language;

/**
 * อ่านค่าสถานะ
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * @var array
     */
    private $statuses;
    /**
     * @var array
     */
    private $colors;
    /**
     * @var mixed
     */
    private $helpdesk_statuses;

    /**
     * Query รายการสถานะทั้งหมดที่สามารถเผยแพร่ได้
     *
     * @param array $statuses
     *
     * @return array
     */
    public static function all($statuses)
    {
        return \Kotchasan\Model::createQuery()
            ->select('category_id', 'topic', 'color', 'type')
            ->from('category')
            ->where(array(
                array('type', $statuses),
                array('published', 1)
            ))
            ->order('category_id')
            ->toArray()
            ->execute();
    }

    /**
     * อ่านค่าสถานะทั้งหมด
     *
     * @return static
     */
    public static function create()
    {
        $obj = new static;
        $obj->helpdesk_statuses = Language::get('HELPDESK_CATEGORIES', []);
        $obj->helpdesk_statuses += Language::get('HELPDESK_STATUSES', []);
        $obj->statuses = [];
        $obj->colors = [];
        foreach (self::all(array_keys($obj->helpdesk_statuses)) as $item) {
            $obj->statuses[$item['type']][$item['category_id']] = $item['topic'];
            $obj->colors[$item['type']][$item['category_id']] = $item['color'];
        }
        return $obj;
    }

    /**
     * อ่านค่าสี status
     *
     * @param int $id
     *
     * @return string
     */
    public function statusColor($id)
    {
        return $this->color('ticketstatus', $id);
    }

    /**
     * อ่านค่า priority
     *
     * @param int $id
     *
     * @return string
     */
    public function priorityColor($id)
    {
        return $this->color('ticketpriority', $id);
    }

    /**
     * อ่านค่า status
     *
     * @param int $id
     *
     * @return string
     */
    public function status($id)
    {
        return $this->get('ticketstatus', $id);
    }

    /**
     * อ่านค่าสี priority
     *
     * @param int $id
     *
     * @return string
     */
    public function priority($id)
    {
        return $this->get('ticketpriority', $id);
    }

    /**
     * อ่านค่าสีที่ $id
     *
     * @param string $type
     * @param int $id
     *
     * @return string
     */
    private function color($type, $id)
    {
        return isset($this->colors[$type][$id]) ? $this->colors[$type][$id] : '#666666';
    }

    /**
     * อ่านสถานะที่ $id
     *
     * @param string $type
     * @param int $id
     *
     * @return string
     */
    private function get($type, $id)
    {
        return isset($this->statuses[$type][$id]) ? $this->statuses[$type][$id] : 'Unknow';
    }

    /**
     * คืนค่าสถานะ สำหรับใส่ลงใน select
     *
     * @param string $type
     *
     * @return array
     */
    public function toSelect($type)
    {
        return isset($this->statuses[$type]) ? $this->statuses[$type] : [];
    }

    /**
     * คืนค่าชื่อสถานะ
     *
     * @param string $type
     *
     * @return array
     */
    public function name($type)
    {
        return isset($this->helpdesk_statuses[$type]) ? $this->helpdesk_statuses[$type] : '';
    }
}
