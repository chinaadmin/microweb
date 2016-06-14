<?php
/**
 * 用户发票模型
 * @author cwh
 * @date 2015-05-27
 */
namespace Home\Model;
use Common\Model\HomebaseModel;
class InvoiceModel extends HomebaseModel{

    protected $tableName = 'user_invoice';

    public $_validate = [
        ['invoice_payee','require','发票抬头不能为空'],
    ];

}