<?php
/**
 * @filesource modules/helpdesk/models/history.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Helpdesk\History;

use Kotchasan\Database\Sql;

/**
 * module=helpdesk-history
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Query ข้อมูลสำหรับส่งให้กับ DataTable
     *
     * @param array $params
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable($params)
    {
        $where = array(
            array('R.customer_id', $params['customer_id'])
        );
        if (!empty($params['priority'])) {
            $where[] = array('R.priority', $params['priority']);
        }
        if ($params['status'] > 0) {
            $where[] = array('R.status', $params['status']);
        }
        if (!empty($params['category'])) {
            $where[] = array('R.category', $params['category']);
        }
        if (!empty($params['from'])) {
            $where[] = array(Sql::DATE('R.create_date'), '>=', $params['from']);
        }
        if (!empty($params['to'])) {
            $where[] = array(Sql::DATE('R.create_date'), '<=', $params['to']);
        }
        $q1 = static::createQuery()
            ->select('helpdesk_id', Sql::MAX('id', 'max_id'))
            ->from('helpdesk_status')
            ->groupBy('helpdesk_id');
        return static::createQuery()
            ->select('R.id', 'R.customer_id', 'C.name customer', 'R.subject', 'R.ticket_no', 'R.priority', 'R.category', 'R.create_date', 'S.agent_id', 'S.create_date latest', 'R.status')
            ->from('helpdesk R')
            ->join(array($q1, 'T'), 'LEFT', array('T.helpdesk_id', 'R.id'))
            ->join('helpdesk_status S', 'LEFT', array('S.id', 'T.max_id'))
            ->join('user C', 'LEFT', array('C.id', 'R.customer_id'))
            ->where($where);
    }
}
