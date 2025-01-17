<?php

namespace App\Interfaces;

interface AuditRepositoryInterface extends BaseRepositoryInterface
{
    public function getAuditsByAdmissions($admissions = []);

    public function getAuditsByDateRange($from, $to);
}