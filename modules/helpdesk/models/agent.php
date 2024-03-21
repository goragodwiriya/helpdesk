<?php
/**
 * @filesource modules/helpdesk/modules/agent.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\Agent;

/**
 * อ่านรายชื่อ Agent ทั้งหมด
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * @var mixed
     */
    private $agents;

    /**
     * Query รายชื่อ Agent
     *
     * @return array
     */
    public static function all()
    {
        return \Kotchasan\Model::createQuery()
            ->select('id', 'name')
            ->from('user')
            ->where(array(
                array('active', 1),
                array('permission', 'LIKE', '%,helpdesk_agent,%')
            ))
            ->order('id')
            ->toArray()
            ->execute();
    }

    /**
     * อ่านรายชื่อ Agent
     *
     * @return static
     */
    public static function create()
    {
        $obj = new static;
        $obj->agents = [];
        foreach (self::all() as $item) {
            $obj->agents[$item['id']] = $item['name'];
        }
        return $obj;
    }

    /**
     * อ่านรายชื่อ Agentสำหรับใส่ลงใน select
     *
     * @return array
     */
    public function toSelect()
    {
        return $this->agents;
    }

    /**
     * อ่านชื่อช่างที่ $id
     *
     * @param int $id
     *
     * @return string
     */
    public function get($id)
    {
        return isset($this->agents[$id]) ? $this->agents[$id] : '';
    }
}
