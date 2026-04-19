@extends('layouts.app')

@section('title', 'Admin Dashboard')

@push('styles')
<style>
    .admin-shell {
        min-height: 100vh;
        padding: 24px 16px;
    }

    .admin-frame {
        max-width: 1280px;
        margin: 0 auto;
    }

    .admin-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .admin-user {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .admin-avatar {
        width: 60px;
        height: 60px;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid rgba(255, 255, 255, 0.22);
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #fff;
        font-weight: 700;
        font-size: 1.5rem;
    }

    .admin-layout {
        display: grid;
        grid-template-columns: 1fr;
        gap: 18px;
    }

    .admin-sidebar {
        background: #2c3d48;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 12px;
    }

    .admin-menu {
        display: grid;
        gap: 8px;
    }

    .admin-menu-link {
        display: block;
        padding: 15px 16px;
        border-radius: 10px;
        color: rgba(255, 255, 255, 0.9);
        font-size: 1.06rem;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid transparent;
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }

    .admin-menu-link:hover {
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(255, 255, 255, 0.08);
    }

    .admin-menu-link.active {
        background: #ff7a00;
        border-color: #ff9b47;
        color: #fff;
    }

    .admin-content {
        background: rgba(17, 63, 70, 0.78);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 26px;
    }

    .admin-content h2 {
        margin: 0 0 8px;
        color: #fff;
        font-size: 2rem;
        line-height: 1.15;
        font-weight: 800;
    }

    .admin-content p {
        margin: 0;
        color: rgba(255, 255, 255, 0.78);
        font-size: 1.05rem;
    }

    .admin-content-box {
        margin-top: 22px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.18);
        background: rgba(255, 255, 255, 0.04);
        padding: 20px;
    }

    .admin-content-box.no-outer-border {
        border: 0;
        background: transparent;
        padding: 0;
    }

    .admin-content-action {
        display: inline-flex;
        align-items: center;
        padding: 11px 18px;
        border-radius: 10px;
        background: #ff7a00;
        color: #fff;
        text-decoration: none;
        font-weight: 700;
        transition: background-color 0.2s ease;
    }

    .admin-content-action:hover {
        background: #e86f00;
    }

    .admin-alert {
        margin-bottom: 12px;
        border-radius: 10px;
        padding: 10px 12px;
        font-size: 0.94rem;
        border: 1px solid transparent;
    }

    .admin-alert-success {
        background: rgba(34, 197, 94, 0.15);
        color: #bbf7d0;
        border-color: rgba(34, 197, 94, 0.35);
    }

    .admin-alert-error {
        background: rgba(239, 68, 68, 0.15);
        color: #fecaca;
        border-color: rgba(239, 68, 68, 0.35);
    }

    .admin-filter-input {
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        background: rgba(255, 255, 255, 0.08) !important;
        color: #ffffff;
        padding: 10px 12px;
        outline: none;
        -webkit-appearance: none;
        appearance: none;
    }

    .admin-filter-input::placeholder {
        color: rgba(255, 255, 255, 0.62);
    }

    .admin-filter-input::-webkit-calendar-picker-indicator {
        filter: invert(1) brightness(1.25);
        cursor: pointer;
    }

    .admin-attendance-filter-grid {
        display: grid;
        grid-template-columns: 1fr;
        row-gap: 14px;
        column-gap: 14px;
    }

    @media (min-width: 768px) {
        .admin-attendance-filter-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            column-gap: 16px;
            row-gap: 16px;
        }
    }

    .admin-table-wrap {
        overflow-x: auto;
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.16);
        background: rgba(255, 255, 255, 0.03);
    }

    .admin-table-wrap-no-border {
        border: 0;
    }

    .admin-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 960px;
        color: rgba(255, 255, 255, 0.92);
        font-size: 0.97rem;
    }

    .admin-table thead {
        background: rgba(255, 255, 255, 0.08);
    }

    .admin-table th,
    .admin-table td {
        text-align: left;
        padding: 12px 14px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        vertical-align: top;
    }

    .admin-status-active {
        color: #60a5fa;
        font-weight: 700;
    }

    .admin-status-inactive {
        color: #fda4af;
        font-weight: 700;
    }

    .admin-action-link {
        color: #fb923c;
        text-decoration: none;
        font-weight: 700;
        background: none;
        border: 0;
        padding: 0;
        cursor: pointer;
    }

    .admin-action-link:hover {
        color: #fdba74;
    }

    .admin-actions-dropdown {
        position: relative;
        min-width: 0;
    }

    .admin-actions-dropdown > summary {
        list-style: none;
    }

    .admin-actions-dropdown > summary::-webkit-details-marker {
        display: none;
    }

    .admin-action-dropdown-toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 11px;
        border-radius: 8px;
        border: 0;
        background: rgba(255, 255, 255, 0.04);
        color: #fb923c;
        font-weight: 700;
        cursor: pointer;
        user-select: none;
    }

    .admin-action-dropdown-toggle::after {
        content: '▾';
        font-size: 0.8rem;
        line-height: 1;
    }

    .admin-actions-menu {
        position: absolute;
        right: 0;
        top: calc(100% + 8px);
        z-index: 30;
        width: 190px;
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        background: #1f4952;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
        padding: 6px;
        display: grid;
        gap: 4px;
    }

    .admin-action-menu-item {
        width: 100%;
        display: block;
        text-align: left;
        padding: 8px 10px;
        border-radius: 7px;
        color: #fb923c;
        text-decoration: none;
        background: transparent;
        border: 0;
        font-weight: 700;
        cursor: pointer;
    }

    .admin-action-menu-item:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #fdba74;
    }

    .admin-table-users {
        width: 100%;
        min-width: 100%;
        table-layout: fixed;
    }

    .admin-table-users th,
    .admin-table-users td {
        padding: 10px 12px;
        word-break: break-word;
    }

    .admin-table-users th:nth-child(1),
    .admin-table-users td:nth-child(1) {
        width: 12%;
        white-space: nowrap;
    }

    .admin-table-users th:nth-child(2),
    .admin-table-users td:nth-child(2) {
        width: 12%;
    }

    .admin-table-users th:nth-child(3),
    .admin-table-users td:nth-child(3) {
        width: 17%;
    }

    .admin-table-users th:nth-child(4),
    .admin-table-users td:nth-child(4) {
        width: 13%;
    }

    .admin-table-users th:nth-child(5),
    .admin-table-users td:nth-child(5) {
        width: 13%;
    }

    .admin-table-users th:nth-child(6),
    .admin-table-users td:nth-child(6) {
        width: 11%;
        white-space: nowrap;
    }

    .admin-table-users th:nth-child(7),
    .admin-table-users td:nth-child(7) {
        width: 10%;
        white-space: nowrap;
    }

    .admin-table-users th:nth-child(8),
    .admin-table-users td:nth-child(8) {
        width: 12%;
        white-space: nowrap;
    }

    .admin-table-attendance {
        min-width: 1700px;
        table-layout: fixed;
    }

    .admin-table-attendance th,
    .admin-table-attendance td {
        vertical-align: middle;
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .admin-table-attendance th {
        white-space: nowrap;
    }

    .admin-table-attendance th:nth-child(1),
    .admin-table-attendance td:nth-child(1) { width: 9%; }
    .admin-table-attendance th:nth-child(2),
    .admin-table-attendance td:nth-child(2) { width: 12%; }
    .admin-table-attendance th:nth-child(3),
    .admin-table-attendance td:nth-child(3) { width: 10%; }
    .admin-table-attendance th:nth-child(4),
    .admin-table-attendance td:nth-child(4) { width: 10%; }
    .admin-table-attendance th:nth-child(5),
    .admin-table-attendance td:nth-child(5) { width: 9%; }
    .admin-table-attendance th:nth-child(6),
    .admin-table-attendance td:nth-child(6) { width: 8%; }
    .admin-table-attendance th:nth-child(7),
    .admin-table-attendance td:nth-child(7) { width: 9%; }
    .admin-table-attendance th:nth-child(8),
    .admin-table-attendance td:nth-child(8) { width: 11%; }
    .admin-table-attendance th:nth-child(9),
    .admin-table-attendance td:nth-child(9) { width: 8%; }
    .admin-table-attendance th:nth-child(10),
    .admin-table-attendance td:nth-child(10) { width: 10%; }
    .admin-table-attendance th:nth-child(11),
    .admin-table-attendance td:nth-child(11) { width: 11%; }
    .admin-table-attendance th:nth-child(12),
    .admin-table-attendance td:nth-child(12) { width: 8%; }

    .admin-table-leave-balance {
        min-width: 1400px;
        table-layout: fixed;
    }

    .admin-table-leave-balance th,
    .admin-table-leave-balance td {
        padding: 11px 12px;
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .admin-table-leave-balance th:nth-child(1),
    .admin-table-leave-balance td:nth-child(1) { width: 16%; }
    .admin-table-leave-balance th:nth-child(2),
    .admin-table-leave-balance td:nth-child(2) { width: 10%; }
    .admin-table-leave-balance th:nth-child(3),
    .admin-table-leave-balance td:nth-child(3) { width: 10%; }
    .admin-table-leave-balance th:nth-child(4),
    .admin-table-leave-balance td:nth-child(4) { width: 10%; }
    .admin-table-leave-balance th:nth-child(5),
    .admin-table-leave-balance td:nth-child(5) { width: 10%; }
    .admin-table-leave-balance th:nth-child(6),
    .admin-table-leave-balance td:nth-child(6) { width: 10%; }
    .admin-table-leave-balance th:nth-child(7),
    .admin-table-leave-balance td:nth-child(7) { width: 8%; }
    .admin-table-leave-balance th:nth-child(8),
    .admin-table-leave-balance td:nth-child(8) { width: 8%; }
    .admin-table-leave-balance th:nth-child(9),
    .admin-table-leave-balance td:nth-child(9) { width: 10%; }
    .admin-table-leave-balance th:nth-child(10),
    .admin-table-leave-balance td:nth-child(10) { width: 8%; }

    .admin-inline-separator {
        color: rgba(255, 255, 255, 0.55);
        margin: 0 6px;
    }

    .admin-form-grid {
        display: grid;
        gap: 10px;
        grid-template-columns: 1fr;
    }

    .leave-balance-card {
        padding: 26px;
    }

    .leave-balance-card .admin-form-grid {
        gap: 14px;
    }

    .leave-balance-card .rp-assign-label {
        margin-bottom: 8px;
        font-size: 1.1rem;
    }

    .admin-user-toolbar {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .admin-user-search {
        display: flex;
        flex-direction: column;
        gap: 12px;
        flex: 1 1 auto;
    }

    .admin-user-search .admin-input {
        flex: 1 1 auto;
    }

    .admin-add-user-btn {
        align-self: flex-start;
    }

    .rp-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .rp-card {
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.04);
        padding: 16px;
    }

    .rp-card h3 {
        margin: 0 0 10px;
        color: #fff;
        font-size: 2rem;
        line-height: 1.1;
        font-weight: 800;
    }

    .rp-add-link {
        color: #a855f7;
        text-decoration: underline;
        font-size: 1.2rem;
        font-weight: 500;
        background: transparent;
        border: 0;
        padding: 0;
        cursor: pointer;
        margin-bottom: 10px;
    }

    .rp-table {
        width: 100%;
        border-collapse: collapse;
    }

    .rp-table th,
    .rp-table td {
        padding: 10px 10px;
        border-bottom: 1px solid rgba(255, 149, 59, 0.7);
        text-align: left;
        color: #fff;
        font-size: 1.05rem;
    }

    .rp-table tr:last-child td {
        border-bottom: 0;
    }

    .rp-modal {
        position: fixed;
        inset: 0;
        z-index: 60;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.45);
        padding: 16px;
    }

    .rp-modal.open {
        display: flex;
    }

    .rp-modal-card {
        width: min(600px, 96vw);
        border-radius: 14px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        background: #e5e7eb;
        padding: 24px;
        color: #1f2937;
    }

    .rp-modal-card h4 {
        margin: 0 0 20px;
        font-size: 2rem;
        line-height: 1.1;
        font-weight: 800;
        color: #0f172a;
    }
    
    #passwordResetModal .admin-form-grid {
        gap: 16px;
    }

    .rp-modal-label {
        display: block;
        margin-bottom: 6px;
        font-size: 1rem;
        color: #0f172a;
        font-weight: 600;
    }

    .rp-modal-input,
    .rp-modal-select {
        width: 100%;
        border: 2px solid #f59e0b;
        border-radius: 9px;
        padding: 12px 14px;
        font-size: 1.1rem;
        color: #000;
        background: #ffffff;
        margin-bottom: 10px;
        font-weight: 600;
        letter-spacing: 0.05em;
    }
    
    .rp-modal-input::placeholder {
        color: #3f3f3f;
        font-weight: 600;
        opacity: 1;
    }
    
    input[type="password"].rp-modal-input {
        letter-spacing: 0.3em;
        font-size: 1.4rem;
    }

    .rp-modal-actions {
        display: flex;
        gap: 10px;
        margin-top: 6px;
    }

    .rp-assign-card {
        margin-top: 16px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.04);
        padding: 16px;
    }

    .rp-assign-card h3 {
        margin: 0 0 14px;
        color: #fff;
        font-size: 2rem;
        line-height: 1.1;
        font-weight: 800;
    }

    .rp-assign-label {
        display: block;
        margin-bottom: 6px;
        color: rgba(255, 255, 255, 0.85);
        font-size: 1rem;
    }

    .rp-assign-select {
        width: 100%;
        border: 1px solid rgba(255, 149, 59, 0.8);
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        font-size: 1rem;
        padding: 10px 12px;
        margin-bottom: 12px;
    }

    .rp-assign-select option {
        color: #111827;
    }

    .rp-assign-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
    }

    .rp-assign-table th,
    .rp-assign-table td {
        padding: 10px 10px;
        border-bottom: 1px solid rgba(255, 149, 59, 0.7);
        text-align: left;
        color: #fff;
        font-size: 1.05rem;
    }

    .rp-assign-table tr:last-child td {
        border-bottom: 0;
    }

    .rp-save-btn {
        border: 0;
        border-radius: 10px;
        background: #ff7a00;
        color: #fff;
        font-size: 1.05rem;
        font-weight: 700;
        padding: 10px 18px;
        cursor: pointer;
    }

    .rp-cancel-btn {
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        background: #f8fafc;
        color: #334155;
        font-size: 1.05rem;
        font-weight: 700;
        padding: 10px 18px;
        cursor: pointer;
    }

    .admin-input,
    .admin-select {
        width: 100%;
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        padding: 10px 12px;
        outline: none;
    }

    .admin-select option {
        color: #0f172a;
    }

    .admin-select-dark {
        background: #ffffff;
        border-color: #cbd5e1;
        color: #1f2937;
    }
    
    #passwordResetModal .admin-input {
        background: #ffffff;
        border: 2px solid #f59e0b;
        color: #000;
        padding: 14px 16px;
        font-size: 1.15rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        margin-bottom: 14px;
    }
    
    #passwordResetModal input[type="password"].admin-input::placeholder {
        color: #4b5563;
        font-weight: 600;
        opacity: 1;
    }

    #departmentModal .admin-input,
    #departmentModal .admin-select {
        background: #ffffff;
        border-color: #cbd5e1;
        color: #1f2937;
    }

    #departmentModal .admin-input::placeholder {
        color: #64748b;
        opacity: 1;
    }

    #departmentModal .admin-select option {
        color: #1f2937;
    }

    #userEditModal .admin-input,
    #userEditModal .admin-select {
        background: #ffffff;
        border-color: #cbd5e1;
        color: #1f2937;
    }

    #userEditModal .admin-input::placeholder {
        color: #64748b;
        opacity: 1;
    }

    #userEditModal .admin-select option {
        color: #1f2937;
    }

    #deactivateConfirmModal .rp-modal-card {
        background: linear-gradient(160deg, #0f4c5c, #0b3f4b);
        border-color: rgba(125, 211, 252, 0.28);
        color: #e6fffb;
    }

    #deactivateConfirmModal .rp-modal-card h4 {
        color: #ecfeff;
    }

    #deactivateConfirmModal .rp-save-btn {
        background: #14b8a6;
    }

    #deactivateConfirmModal .rp-save-btn:hover {
        background: #0d9488;
    }

    #deactivateConfirmModal .rp-cancel-btn {
        background: rgba(255, 255, 255, 0.12);
        border-color: rgba(255, 255, 255, 0.28);
        color: #ecfeff;
    }

    @media (min-width: 768px) {
        .admin-form-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .admin-user-toolbar {
            flex-direction: row;
            align-items: center;
        }

        .admin-user-search {
            flex-direction: row;
            align-items: center;
        }

        .rp-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (min-width: 1024px) {
        .admin-shell {
            padding: 28px;
        }

        .admin-layout {
            grid-template-columns: 260px minmax(0, 1fr);
        }
    }
</style>
@endpush

@section('content')
    @php
        $sections = [
            [
                'key' => 'user-management',
                'label' => 'User Management',
                'summary' => 'Create, update, deactivate, and search employee accounts.',
            ],
            [
                'key' => 'department-hod-management',
                'label' => 'Department & HoD Management',
                'summary' => 'Assign departments and maintain department-level head mappings.',
            ],
            [
                'key' => 'roles-permissions',
                'label' => 'Roles & Permissions',
                'summary' => 'Define role access levels and permission groups for modules.',
            ],
            [
                'key' => 'leave-types',
                'label' => 'Leave Types',
                'summary' => 'Manage leave categories, entitlement rules, and policy limits.',
            ],
            [
                'key' => 'leave-balance',
                'label' => 'Leave Balance',
                'summary' => 'Review and adjust leave balances for employees.',
            ],
            [
                'key' => 'attendance-logs',
                'label' => 'Attendance Logs',
                'summary' => 'Inspect clock-in and clock-out history records.',
            ],
            [
                'key' => 'leave-records',
                'label' => 'Leave Records',
                'summary' => 'Track leave request flow and final approvals.',
                'ctaLabel' => 'Open Leave Records',
                'ctaRoute' => $isMs ? route('ms.leave.requests') : null,
            ],
            [
                'key' => 'staff-on-tour',
                'label' => 'Staff on Tour',
                'summary' => 'View all staff tour records submitted by employees.',
            ],
            [
                'key' => 'reports',
                'label' => 'Reports',
                'summary' => 'Generate and export attendance and leave analytics.',
            ],
            [
                'key' => 'settings',
                'label' => 'Settings',
                'summary' => 'Configure system-wide defaults and admin preferences.',
            ],
        ];

        $activeSection = $activeSection ?? request('section', 'roles-permissions');
        $attendanceFilters = $attendanceFilters ?? [
            'from_date' => '',
            'to_date' => '',
            'employee' => '',
        ];
        $userFilters = $userFilters ?? [
            'search' => '',
        ];
        $showCreateUserForm = $showCreateUserForm ?? request()->boolean('create_user');
        $showAddDepartmentForm = $showAddDepartmentForm ?? request()->boolean('add_department');
        $managedDepartments = $managedDepartments ?? null;
        $departmentHodCandidates = $departmentHodCandidates ?? collect();
        $editingDepartment = $editingDepartment ?? null;
        $assigningDepartment = $assigningDepartment ?? null;
        $managedRoles = $managedRoles ?? collect();
        $managedPermissions = $managedPermissions ?? collect();
        $selectedRoleIdForPermissions = $selectedRoleIdForPermissions ?? (int) request('assign_role_id', 0);
        $assignedPermissionIds = $assignedPermissionIds ?? [];
        $managedLeaveTypes = $managedLeaveTypes ?? null;
        $showAddLeaveTypeForm = $showAddLeaveTypeForm ?? request()->boolean('add_leave_type');
        $editingLeaveType = $editingLeaveType ?? null;
        $managedLeaveRecords = $managedLeaveRecords ?? null;
        $managedStaffTours = $managedStaffTours ?? null;
        $leaveBalanceEmployees = $leaveBalanceEmployees ?? collect();
        $leaveBalanceTypes = $leaveBalanceTypes ?? collect();
        $bonusIpdEmployees = $bonusIpdEmployees ?? collect();
        $activeSectionData = collect($sections)->firstWhere('key', $activeSection)
            ?? collect($sections)->firstWhere('key', 'roles-permissions');

        $roleOptions = [
            1 => 'Medical Superintendent',
            2 => 'HoD',
            3 => 'Employee',
        ];

        $activeSectionLabel = $activeSectionData['label'];
        $activeSectionSummary = $activeSectionData['summary'];
        $activeSectionRoute = $activeSectionData['ctaRoute'] ?? null;
        $activeSectionRouteLabel = $activeSectionData['ctaLabel'] ?? null;
    @endphp

    <div class="admin-shell">
        <div class="admin-frame">
            <div class="admin-header">
                <div class="admin-user">
                    @if($user->profile_picture && file_exists(public_path($user->profile_picture)))
                        <img src="{{ asset($user->profile_picture) }}" alt="Profile Picture" class="w-16 h-16 rounded-full object-cover border-2 border-white/20">
                    @else
                        <div class="admin-avatar">
                            <span>{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                        </div>
                    @endif
                    <div>
                        <h1 class="mb-1 text-3xl font-bold text-white">Admin Dashboard</h1>
                        <p class="text-white/70"><strong class="text-white">{{ $user->name }}</strong></p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                        Logout
                    </button>
                </form>
            </div>

            <div class="admin-layout">
                <aside class="admin-sidebar">
                    <nav class="admin-menu">
                        @foreach($sections as $section)
                            @php $isActive = $activeSection === $section['key']; @endphp
                            <a
                                href="{{ route('admin.dashboard', ['section' => $section['key']]) }}"
                                class="admin-menu-link {{ $isActive ? 'active' : '' }}"
                            >
                                {{ $section['label'] }}
                            </a>
                        @endforeach
                    </nav>
                </aside>

                <section class="admin-content">
                    <h2>{{ $activeSectionLabel }}</h2>
                    @if(!in_array($activeSection, ['user-management', 'department-hod-management', 'leave-types', 'leave-balance'], true))
                        <p>{{ $activeSectionSummary }}</p>
                    @endif

                    <div class="admin-content-box {{ in_array($activeSection, ['roles-permissions', 'leave-types', 'user-management', 'leave-balance', 'attendance-logs'], true) ? 'no-outer-border' : '' }}">
                    @if(session('success'))
                        <div class="admin-alert admin-alert-success">{{ session('success') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="admin-alert admin-alert-error">{{ session('error') }}</div>
                    @endif

                    @if($activeSection === 'department-hod-management')
                        <div class="mb-4 flex items-center justify-between">
                            <button type="button" class="admin-action-link" style="font-size:1.05rem;" onclick="openDepartmentModal()">
                                + Add Department
                            </button>
                        </div>

                        <div id="departmentModal" class="rp-modal" aria-hidden="true" data-open-on-load="{{ $errors->any() ? '1' : '0' }}">
                            <div class="rp-modal-card">
                                <h4>Add Department</h4>

                                <form method="POST" action="{{ route('admin.departments.store') }}" class="admin-form-grid">
                                    @csrf
                                    <input type="text" name="name" value="{{ old('name') }}" class="admin-input" placeholder="Department name" required>

                                    <select name="status" class="admin-select admin-select-dark" required>
                                        <option value="Active" @selected(old('status', 'Active') === 'Active')>Active</option>
                                        <option value="Inactive" @selected(old('status') === 'Inactive')>Inactive</option>
                                    </select>

                                    <div style="grid-column:1 / -1; display:flex; gap:10px;">
                                        <button type="submit" class="admin-content-action">Save Department</button>
                                        <button type="button" class="admin-content-action" style="background:#475569;" onclick="closeDepartmentModal()">Cancel</button>
                                    </div>
                                </form>

                                @if($errors->any())
                                    <div class="admin-alert admin-alert-error" style="margin-top:10px;">
                                        @foreach($errors->all() as $error)
                                            <div>{{ $error }}</div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <script>
                            function openDepartmentModal() {
                                document.getElementById('departmentModal').classList.add('open');
                            }

                            function closeDepartmentModal() {
                                document.getElementById('departmentModal').classList.remove('open');
                            }

                            const shouldOpenDepartmentModal = document
                                .getElementById('departmentModal')
                                .getAttribute('data-open-on-load') === '1';

                            if (shouldOpenDepartmentModal) {
                                openDepartmentModal();
                            }
                        </script>

                        <div class="overflow-x-auto">
                            <table class="admin-table admin-table-users">
                                <thead>
                                    <tr>
                                        <th>Department</th>
                                        <th>HOD</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($managedDepartments ?? collect()) as $department)
                                        @php $deptStatus = ucfirst(strtolower((string) ($department->status ?: 'Active'))); @endphp
                                        <tr>
                                            <td>{{ $department->name }}</td>
                                            <td>{{ $department->hod?->name ?: '-' }}</td>
                                            <td>
                                                <span class="{{ strtolower($deptStatus) === 'active' ? 'admin-status-active' : 'admin-status-inactive' }}">
                                                    {{ $deptStatus }}
                                                </span>
                                            </td>
                                            <td>
                                                <a class="admin-action-link" href="{{ route('admin.dashboard', ['section' => 'department-hod-management', 'edit_department' => $department->id]) }}">Edit</a>
                                                <span class="admin-inline-separator">|</span>
                                                <a class="admin-action-link" href="{{ route('admin.dashboard', ['section' => 'department-hod-management', 'assign_department' => $department->id]) }}">Assign HOD</a>
                                                <span class="admin-inline-separator">|</span>
                                                <form method="POST" action="{{ route('admin.departments.toggleStatus', $department) }}" style="display:inline;">
                                                    @csrf
                                                    <button type="submit" class="admin-action-link">
                                                        {{ strtolower($deptStatus) === 'active' ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" style="text-align:center; color: rgba(255,255,255,0.68);">No departments found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($managedDepartments)
                            <div class="mt-4">
                                {{ $managedDepartments->links() }}
                            </div>
                        @endif

                        @if($editingDepartment)
                            <div class="mt-6 rounded-lg border border-white/20 bg-white/5 p-4">
                                <h3 style="margin:0 0 10px; color:#fff; font-size:1.2rem; font-weight:700;">Edit Department: {{ $editingDepartment->name }}</h3>

                                <form method="POST" action="{{ route('admin.departments.update', $editingDepartment) }}" class="admin-form-grid">
                                    @csrf
                                    <input type="text" name="name" value="{{ old('name', $editingDepartment->name) }}" class="admin-input" placeholder="Department name" required>

                                    <select name="status" class="admin-select" required>
                                        <option value="Active" @selected(old('status', $editingDepartment->status) === 'Active')>Active</option>
                                        <option value="Inactive" @selected(old('status', $editingDepartment->status) === 'Inactive')>Inactive</option>
                                    </select>

                                    <div style="grid-column:1 / -1; display:flex; gap:10px;">
                                        <button type="submit" class="admin-content-action">Save Changes</button>
                                        <a href="{{ route('admin.dashboard', ['section' => 'department-hod-management']) }}" class="admin-content-action" style="background:#475569;">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        @endif

                        @if($assigningDepartment)
                            <div class="mt-6 rounded-lg border border-white/20 bg-white/5 p-4">
                                <h3 style="margin:0 0 10px; color:#fff; font-size:1.2rem; font-weight:700;">Assign HoD: {{ $assigningDepartment->name }}</h3>

                                <form method="POST" action="{{ route('admin.departments.assignHod', $assigningDepartment) }}" class="admin-form-grid">
                                    @csrf

                                    <select name="hod_user_id" class="admin-select" required>
                                        <option value="">Select Employee</option>
                                        @foreach($departmentHodCandidates as $hodCandidate)
                                            <option value="{{ $hodCandidate->id }}" @selected((int) old('hod_user_id', $assigningDepartment->hod_user_id) === (int) $hodCandidate->id)>
                                                {{ $hodCandidate->name }}{{ $hodCandidate->eid ? ' (' . $hodCandidate->eid . ')' : '' }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <div style="grid-column:1 / -1; display:flex; gap:10px;">
                                        <button type="submit" class="admin-content-action">Assign HOD</button>
                                        <a href="{{ route('admin.dashboard', ['section' => 'department-hod-management']) }}" class="admin-content-action" style="background:#475569;">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        @endif
                    @elseif($activeSection === 'roles-permissions')
                        <div class="rp-grid">
                            <div class="rp-card">
                                <h3>Manage Roles</h3>
                                <button type="button" class="rp-add-link" onclick="openRoleModal()">+ Add Role</button>

                                <table class="rp-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Role Name</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($managedRoles as $role)
                                            @php $roleStatus = ucfirst(strtolower((string) ($role->status ?? 'Active'))); @endphp
                                            <tr>
                                                <td>{{ $role->id }}</td>
                                                <td>{{ $role->name }}</td>
                                                <td>{{ strtolower($roleStatus) }}</td>
                                                <td>
                                                    <button
                                                        type="button"
                                                        class="admin-action-link"
                                                        data-id="{{ $role->id }}"
                                                        data-name="{{ $role->name }}"
                                                        data-status="{{ $roleStatus }}"
                                                        data-assign-url="{{ route('admin.roles.assignUser', $role) }}"
                                                        onclick="openRoleModalFromButton(this)"
                                                    >
                                                        Edit
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" style="color: rgba(255,255,255,0.75);">No roles found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="rp-card">
                                <h3>Permissions</h3>
                                <button type="button" class="rp-add-link" onclick="openPermissionModal()">+ Add Permission</button>

                                <table class="rp-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Permission Name</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($managedPermissions as $permission)
                                            @php $permissionStatus = ucfirst(strtolower((string) ($permission->status ?? 'Active'))); @endphp
                                            <tr>
                                                <td>{{ $permission->id }}</td>
                                                <td>{{ $permission->name }}</td>
                                                <td>{{ strtolower($permissionStatus) }}</td>
                                                <td>
                                                    <button
                                                        type="button"
                                                        class="admin-action-link"
                                                        data-id="{{ $permission->id }}"
                                                        data-name="{{ $permission->name }}"
                                                        data-status="{{ $permissionStatus }}"
                                                        onclick="openPermissionModalFromButton(this)"
                                                    >
                                                        Edit
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" style="color: rgba(255,255,255,0.75);">No permissions found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="rp-assign-card">
                            <h3>Assign Permissions</h3>

                            <form method="GET" action="{{ route('admin.dashboard') }}" style="margin-bottom: 10px;">
                                <input type="hidden" name="section" value="roles-permissions">
                                <label class="rp-assign-label" for="assignRoleSelect">Select Role</label>
                                <select id="assignRoleSelect" class="rp-assign-select" name="assign_role_id" onchange="this.form.submit()">
                                    <option value="">-- Select Role --</option>
                                    @foreach($managedRoles as $role)
                                        <option value="{{ $role->id }}" @selected((int) $selectedRoleIdForPermissions === (int) $role->id)>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>

                            <form method="POST" action="{{ route('admin.roles.assignPermissions') }}">
                                @csrf
                                <input type="hidden" name="role_id" value="{{ $selectedRoleIdForPermissions }}">

                                <table class="rp-assign-table">
                                    <thead>
                                        <tr>
                                            <th style="width:48px;"></th>
                                            <th>Permission</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($managedPermissions as $permission)
                                            @php
                                                $permStatus = ucfirst(strtolower((string) ($permission->status ?? 'Active')));
                                                $isAssigned = in_array((int) $permission->id, array_map('intval', $assignedPermissionIds), true);
                                            @endphp
                                            <tr>
                                                <td>
                                                    <input
                                                        type="checkbox"
                                                        name="permission_ids[]"
                                                        value="{{ $permission->id }}"
                                                        @checked($isAssigned)
                                                        @disabled(!$selectedRoleIdForPermissions)
                                                    >
                                                </td>
                                                <td>{{ $permission->name }}</td>
                                                <td>{{ strtolower($permStatus) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" style="color: rgba(255,255,255,0.75);">No permissions found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>

                                <button type="submit" class="rp-save-btn" @disabled(!$selectedRoleIdForPermissions)>
                                    Save Assignments
                                </button>
                            </form>
                        </div>

                        <div id="roleModal" class="rp-modal" aria-hidden="true">
                            <div class="rp-modal-card">
                                <h4 id="roleModalTitle">Add Role</h4>
                                <form method="POST" action="{{ route('admin.roles.save') }}">
                                    @csrf
                                    <input type="hidden" name="role_id" id="roleModalId" value="">

                                    <label class="rp-modal-label" for="roleModalName">Role Name</label>
                                    <input class="rp-modal-input" id="roleModalName" name="name" type="text" required>

                                    <label class="rp-modal-label" for="roleModalStatus">Status</label>
                                    <select class="rp-modal-select" id="roleModalStatus" name="status" required>
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>

                                    <div class="rp-modal-actions">
                                        <button type="submit" class="rp-save-btn">Save</button>
                                        <button type="button" class="rp-cancel-btn" onclick="closeRoleModal()">Cancel</button>
                                    </div>
                                </form>

                                <form method="POST" id="roleAssignUserForm" style="display:none; margin-top:12px;">
                                    @csrf
                                    <label class="rp-modal-label" for="roleAssignUserSelect">Assign User To This Role</label>
                                    <select class="rp-modal-select" id="roleAssignUserSelect" name="user_id" required>
                                        <option value="">Select user</option>
                                        @foreach($roleAssignableUsers as $assignableUser)
                                            <option value="{{ $assignableUser->id }}">
                                                {{ $assignableUser->name }}{{ $assignableUser->eid ? ' (' . $assignableUser->eid . ')' : '' }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <div class="rp-modal-actions">
                                        <button type="submit" class="rp-save-btn">Assign</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div id="permissionModal" class="rp-modal" aria-hidden="true">
                            <div class="rp-modal-card">
                                <h4 id="permissionModalTitle">Add Permission</h4>
                                <form method="POST" action="{{ route('admin.permissions.save') }}">
                                    @csrf
                                    <input type="hidden" name="permission_id" id="permissionModalId" value="">

                                    <label class="rp-modal-label" for="permissionModalName">Permission Name</label>
                                    <input class="rp-modal-input" id="permissionModalName" name="name" type="text" required>

                                    <label class="rp-modal-label" for="permissionModalStatus">Status</label>
                                    <select class="rp-modal-select" id="permissionModalStatus" name="status" required>
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>

                                    <div class="rp-modal-actions">
                                        <button type="submit" class="rp-save-btn">Save</button>
                                        <button type="button" class="rp-cancel-btn" onclick="closePermissionModal()">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <script>
                            function openRoleModal(id = '', name = '', status = 'Active', assignUrl = '') {
                                const modal = document.getElementById('roleModal');
                                const assignForm = document.getElementById('roleAssignUserForm');
                                document.getElementById('roleModalId').value = id || '';
                                document.getElementById('roleModalName').value = name || '';
                                document.getElementById('roleModalStatus').value = status || 'Active';
                                document.getElementById('roleModalTitle').textContent = id ? 'Edit Role' : 'Add Role';

                                if (id && assignUrl) {
                                    assignForm.setAttribute('action', assignUrl);
                                    assignForm.style.display = 'block';
                                } else {
                                    assignForm.setAttribute('action', '');
                                    assignForm.style.display = 'none';
                                }

                                modal.classList.add('open');
                            }

                            function closeRoleModal() {
                                document.getElementById('roleModal').classList.remove('open');
                            }

                            function openRoleModalFromButton(button) {
                                openRoleModal(
                                    button.getAttribute('data-id') || '',
                                    button.getAttribute('data-name') || '',
                                    button.getAttribute('data-status') || 'Active',
                                    button.getAttribute('data-assign-url') || ''
                                );
                            }

                            function openPermissionModal(id = '', name = '', status = 'Active') {
                                const modal = document.getElementById('permissionModal');
                                document.getElementById('permissionModalId').value = id || '';
                                document.getElementById('permissionModalName').value = name || '';
                                document.getElementById('permissionModalStatus').value = status || 'Active';
                                document.getElementById('permissionModalTitle').textContent = id ? 'Edit Permission' : 'Add Permission';
                                modal.classList.add('open');
                            }

                            function closePermissionModal() {
                                document.getElementById('permissionModal').classList.remove('open');
                            }

                            function openPermissionModalFromButton(button) {
                                openPermissionModal(
                                    button.getAttribute('data-id') || '',
                                    button.getAttribute('data-name') || '',
                                    button.getAttribute('data-status') || 'Active'
                                );
                            }
                        </script>
                    @elseif($activeSection === 'leave-balance')
                        <div class="leave-balance-card rounded-lg bg-white/5 p-4">
                            <h3 style="margin:0 0 14px; color:#fff; font-size:1.35rem; font-weight:800;">Set Leave Balance</h3>

                            <form method="POST" action="{{ route('admin.leaveBalances.set') }}" class="admin-form-grid" style="margin-bottom: 16px;">
                                @csrf

                                <div>
                                    <label class="rp-assign-label">Employee</label>
                                    <select name="user_id" class="admin-select" required>
                                        <option value="">Select Employee</option>
                                        @foreach($leaveBalanceEmployees as $employee)
                                            <option value="{{ $employee->id }}" @selected((int) old('user_id') === (int) $employee->id)>
                                                {{ $employee->name }}{{ $employee->eid ? ' (' . $employee->eid . ')' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="rp-assign-label">Leave Type</label>
                                    <select name="leave_type_id" class="admin-select" required>
                                        <option value="">Select Leave Type</option>
                                        @foreach($leaveBalanceTypes as $leaveTypeOption)
                                            <option value="{{ $leaveTypeOption->id }}" @selected((int) old('leave_type_id') === (int) $leaveTypeOption->id)>
                                                {{ $leaveTypeOption->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="rp-assign-label">Max Per Year</label>
                                    <input type="number" step="0.5" min="0" max="365" name="max_per_year" value="{{ old('max_per_year') }}" class="admin-input" required>
                                </div>

                                <div style="grid-column: 1 / -1;">
                                    <button type="submit" class="rp-save-btn">Save</button>
                                </div>
                            </form>

                            <h3 style="margin:0 0 14px; color:#fff; font-size:1.35rem; font-weight:800;">Adjust Leave Balance</h3>

                            <form method="POST" action="{{ route('admin.leaveBalances.adjust') }}" class="admin-form-grid">
                                @csrf

                                <div>
                                    <label class="rp-assign-label">Leave Type</label>
                                    <select name="leave_type_id" class="admin-select" required>
                                        <option value="">Select Leave Type</option>
                                        @foreach($leaveBalanceTypes as $leaveTypeOption)
                                            <option value="{{ $leaveTypeOption->id }}">
                                                {{ $leaveTypeOption->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="rp-assign-label">Adjustment (+/-)</label>
                                    <input type="number" step="0.5" min="-365" max="365" name="adjustment" value="{{ old('adjustment') }}" class="admin-input" required>
                                </div>

                                <div style="grid-column: 1 / -1; margin-top: -4px; color: rgba(255, 255, 255, 0.72); font-size: 0.92rem;">
                                    Adjustment will be applied to all active employees for the selected leave type.
                                </div>

                                <div style="grid-column: 1 / -1;">
                                    <button type="submit" class="rp-save-btn">Apply</button>
                                </div>
                            </form>

                            <h3 style="margin:30px 0 14px; color:#fff; font-size:1.35rem; font-weight:800;">Reset Yearly</h3>
                            <form method="POST" action="{{ route('admin.leaveBalances.resetYearly') }}" style="margin-bottom: 18px;">
                                @csrf
                                <button type="submit" class="rp-save-btn">Reset All</button>
                            </form>

                            <h3 style="margin:0 0 14px; color:#fff; font-size:1.35rem; font-weight:800;">Leave Balance List</h3>

                            <div class="admin-table-wrap">
                                <table class="admin-table admin-table-leave-balance">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Annual Leave</th>
                                            <th>Bereavement Leave</th>
                                            <th>Maternity Leave</th>
                                            <th>Medical Leave</th>
                                            <th>Paternity Leave</th>
                                            <th>Max</th>
                                            <th>Used</th>
                                            <th>Remaining</th>
                                            <th>Year</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($leaveBalanceRows as $balanceRow)
                                            <tr>
                                                <td>{{ $balanceRow['employee_name'] }}</td>
                                                <td>{{ number_format((float) $balanceRow['annual_leave'], 2) }}</td>
                                                <td>{{ number_format((float) $balanceRow['bereavement_leave'], 2) }}</td>
                                                <td>{{ number_format((float) $balanceRow['maternity_leave'], 2) }}</td>
                                                <td>{{ number_format((float) $balanceRow['medical_leave'], 2) }}</td>
                                                <td>{{ number_format((float) $balanceRow['paternity_leave'], 2) }}</td>
                                                <td>{{ number_format((float) $balanceRow['max_per_year'], 2) }}</td>
                                                <td>{{ number_format((float) $balanceRow['used_days'], 2) }}</td>
                                                <td>{{ number_format((float) $balanceRow['remaining_days'], 2) }}</td>
                                                <td>{{ $balanceRow['year'] }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="10" style="color: rgba(255,255,255,0.75);">No leave balance records available for {{ $leaveBalanceYear }}.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @elseif($activeSection === 'leave-types')
                        <div class="mb-4 flex items-center justify-between">
                            <a href="{{ route('admin.dashboard', ['section' => 'leave-types', 'add_leave_type' => 1]) }}" class="admin-action-link" style="font-size:1.05rem;">
                                + Add Leave Type
                            </a>
                        </div>

                        @if($showAddLeaveTypeForm)
                            <div class="mb-5 rounded-lg border border-white/20 bg-white/5 p-4">
                                <h3 style="margin:0 0 10px; color:#fff; font-size:1.2rem; font-weight:700;">Add Leave Type</h3>

                                <form method="POST" action="{{ route('admin.leaveTypes.store') }}" class="admin-form-grid">
                                    @csrf

                                    <input type="text" name="name" value="{{ old('name') }}" class="admin-input" placeholder="Leave type name" required>
                                    <input type="text" name="code" value="{{ old('code') }}" class="admin-input" placeholder="Code (AL, ML, etc.)" required>
                                    <input type="number" step="0.5" min="0" max="365" name="entitlement_days" value="{{ old('entitlement_days', 0) }}" class="admin-input" placeholder="Max per year" required>

                                    <input type="text" name="description" value="{{ old('description') }}" class="admin-input" placeholder="Description" style="grid-column: 1 / -1;">

                                    <select name="status" class="admin-select" required>
                                        <option value="Active" @selected(old('status', 'Active') === 'Active')>Active</option>
                                        <option value="Inactive" @selected(old('status') === 'Inactive')>Inactive</option>
                                    </select>

                                    <div style="grid-column:1 / -1; display:flex; gap:10px;">
                                        <button type="submit" class="admin-content-action">Save Leave Type</button>
                                        <a href="{{ route('admin.dashboard', ['section' => 'leave-types']) }}" class="admin-content-action" style="background:#475569;">Cancel</a>
                                    </div>
                                </form>

                                @if($errors->any())
                                    <div class="admin-alert admin-alert-error" style="margin-top:10px;">
                                        @foreach($errors->all() as $error)
                                            <div>{{ $error }}</div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="overflow-x-auto">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Code</th>
                                        <th>Description</th>
                                        <th>Max/Year</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($managedLeaveTypes ?? collect()) as $leaveType)
                                        @php $leaveTypeStatus = (bool) $leaveType->is_active ? 'Active' : 'Inactive'; @endphp
                                        <tr>
                                            <td>{{ $leaveType->id }}</td>
                                            <td>{{ $leaveType->name }}</td>
                                            <td>{{ strtoupper((string) $leaveType->code) }}</td>
                                            <td>{{ $leaveType->description ?: '-' }}</td>
                                            <td>{{ rtrim(rtrim(number_format((float) $leaveType->entitlement_days, 2), '0'), '.') }}</td>
                                            <td>
                                                <span class="{{ $leaveType->is_active ? 'admin-status-active' : 'admin-status-inactive' }}">{{ $leaveTypeStatus }}</span>
                                            </td>
                                            <td>
                                                <a class="admin-action-link" href="{{ route('admin.dashboard', ['section' => 'leave-types', 'edit_leave_type' => $leaveType->id]) }}">Edit</a>
                                                <span class="admin-inline-separator">|</span>
                                                <form method="POST" action="{{ route('admin.leaveTypes.toggleStatus', $leaveType) }}" style="display:inline;">
                                                    @csrf
                                                    <button type="submit" class="admin-action-link">Toggle Status</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" style="text-align:center; color: rgba(255,255,255,0.68);">No leave types found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($managedLeaveTypes)
                            <div class="mt-4">
                                {{ $managedLeaveTypes->links() }}
                            </div>
                        @endif

                        @if($editingLeaveType)
                            <div class="mt-6 rounded-lg border border-white/20 bg-white/5 p-4">
                                <h3 style="margin:0 0 10px; color:#fff; font-size:1.2rem; font-weight:700;">Edit Leave Type: {{ $editingLeaveType->name }}</h3>

                                <form method="POST" action="{{ route('admin.leaveTypes.update', $editingLeaveType) }}" class="admin-form-grid">
                                    @csrf

                                    <input type="text" name="name" value="{{ old('name', $editingLeaveType->name) }}" class="admin-input" placeholder="Leave type name" required>
                                    <input type="text" name="code" value="{{ old('code', $editingLeaveType->code) }}" class="admin-input" placeholder="Code" required>
                                    <input type="number" step="0.5" min="0" max="365" name="entitlement_days" value="{{ old('entitlement_days', $editingLeaveType->entitlement_days) }}" class="admin-input" placeholder="Max per year" required>

                                    <input type="text" name="description" value="{{ old('description', $editingLeaveType->description) }}" class="admin-input" placeholder="Description" style="grid-column: 1 / -1;">

                                    <select name="status" class="admin-select" required>
                                        <option value="Active" @selected(old('status', $editingLeaveType->is_active ? 'Active' : 'Inactive') === 'Active')>Active</option>
                                        <option value="Inactive" @selected(old('status', $editingLeaveType->is_active ? 'Active' : 'Inactive') === 'Inactive')>Inactive</option>
                                    </select>

                                    <div style="grid-column:1 / -1; display:flex; gap:10px;">
                                        <button type="submit" class="admin-content-action">Save Changes</button>
                                        <a href="{{ route('admin.dashboard', ['section' => 'leave-types']) }}" class="admin-content-action" style="background:#475569;">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        @endif
                    @elseif($activeSection === 'user-management')
                        <div class="mb-4 admin-user-toolbar">
                            <form method="GET" action="{{ route('admin.dashboard') }}" class="admin-user-search">
                                <input type="hidden" name="section" value="user-management">
                                <input
                                    type="text"
                                    name="user_search"
                                    value="{{ $userFilters['search'] }}"
                                    placeholder="Search by name, EID, email, department"
                                    class="admin-input"
                                >
                                <button type="submit" class="admin-content-action">Search</button>
                            </form>

                            <a href="{{ route('admin.dashboard', ['section' => 'user-management', 'create_user' => 1, 'user_search' => $userFilters['search']]) }}" class="admin-content-action admin-add-user-btn">
                                Add New User
                            </a>
                        </div>

                        @if($showCreateUserForm)
                            <div class="mb-8">
                                <h3 style="margin:0 0 10px; color:#fff; font-size:1.2rem; font-weight:700;">Create New User</h3>

                                <form method="POST" action="{{ route('admin.users.store') }}" class="admin-form-grid">
                                    @csrf

                                    <input type="text" name="name" value="{{ old('name') }}" class="admin-input" placeholder="Full name" required>
                                    <input type="email" name="email" value="{{ old('email') }}" class="admin-input" placeholder="Email" required>
                                    <input type="text" name="eid" value="{{ old('eid') }}" class="admin-input" placeholder="EID">

                                    <input type="text" name="designation" value="{{ old('designation') }}" class="admin-input" placeholder="Designation">
                                    <input type="text" name="department" value="{{ old('department') }}" class="admin-input" placeholder="Department">

                                    <select name="role_id" class="admin-select" required>
                                        @foreach($roleOptions as $roleId => $roleLabel)
                                            <option value="{{ $roleId }}" @selected((int) old('role_id', 3) === (int) $roleId)>
                                                {{ $roleLabel }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <select name="status" class="admin-select" required>
                                        <option value="Active" @selected(old('status', 'Active') === 'Active')>Active</option>
                                        <option value="Inactive" @selected(old('status') === 'Inactive')>Inactive</option>
                                    </select>

                                    <input type="password" name="password" class="admin-input" placeholder="Password (min 8 chars)" required>
                                    <input type="password" name="password_confirmation" class="admin-input" placeholder="Confirm password" required>

                                    <div style="grid-column:1 / -1; display:flex; gap:10px;">
                                        <button type="submit" class="admin-content-action">Create User</button>
                                        <a href="{{ route('admin.dashboard', ['section' => 'user-management', 'user_search' => $userFilters['search']]) }}" class="admin-content-action" style="background:#475569;">Cancel</a>
                                    </div>
                                </form>

                                @if($errors->any())
                                    <div class="admin-alert admin-alert-error" style="margin-top:10px;">
                                        @foreach($errors->all() as $error)
                                            <div>{{ $error }}</div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="overflow-x-auto">
                            <table class="admin-table admin-table-users">
                                <thead>
                                    <tr>
                                        <th>Employee ID</th>
                                        <th>EID</th>
                                        <th>Employee Name</th>
                                        <th>Designation</th>
                                        <th>Department</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($managedUsers ?? collect()) as $managedUser)
                                        <tr>
                                            <td>{{ $managedUser->id }}</td>
                                            <td>{{ $managedUser->eid ?: '-' }}</td>
                                            <td>{{ $managedUser->name }}</td>
                                            <td>{{ $managedUser->designation ?: '-' }}</td>
                                            <td>{{ $managedUser->department ?: '-' }}</td>
                                            <td>{{ $managedUser->role_name }}</td>
                                            <td>
                                                @php $statusLabel = ucfirst(strtolower((string) ($managedUser->status ?: 'Active'))); @endphp
                                                <span class="{{ strtolower($statusLabel) === 'active' ? 'admin-status-active' : 'admin-status-inactive' }}">
                                                    {{ $statusLabel }}
                                                </span>
                                            </td>
                                            <td>
                                                <details class="admin-actions-dropdown">
                                                    <summary class="admin-action-dropdown-toggle">Edit</summary>
                                                    <div class="admin-actions-menu">
                                                        <button
                                                            type="button"
                                                            class="admin-action-menu-item"
                                                            onclick="openUserEditModalFromButton(this)"
                                                            data-name="{{ $managedUser->name }}"
                                                            data-eid="{{ $managedUser->eid ?: '' }}"
                                                            data-designation="{{ $managedUser->designation ?: '' }}"
                                                            data-department="{{ $managedUser->department ?: '' }}"
                                                            data-role-id="{{ $managedUser->role_id }}"
                                                            data-status="{{ $managedUser->status ?: 'Active' }}"
                                                            data-update-url="{{ route('admin.users.update', $managedUser) }}"
                                                        >
                                                            Edit
                                                        </button>

                                                        <button
                                                            type="button"
                                                            class="admin-action-menu-item"
                                                            onclick="openPasswordResetModalFromButton(this)"
                                                            data-name="{{ $managedUser->name }}"
                                                            data-reset-url="{{ route('admin.users.resetPassword', $managedUser) }}"
                                                        >
                                                            Reset Password
                                                        </button>

                                                        <form method="POST" action="{{ route('admin.users.deactivate', $managedUser) }}" class="js-deactivate-user-form" data-user-name="{{ $managedUser->name }}">
                                                            @csrf
                                                            <button type="submit" class="admin-action-menu-item">Deactivate</button>
                                                        </form>

                                                        <form method="POST" action="{{ route('admin.users.toggleStatus', $managedUser) }}">
                                                            @csrf
                                                            <button type="submit" class="admin-action-menu-item">Toggle Status</button>
                                                        </form>
                                                    </div>
                                                </details>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" style="text-align:center; color: rgba(255,255,255,0.68);">No users found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($managedUsers)
                            <div class="mt-4">
                                {{ $managedUsers->links() }}
                            </div>
                        @endif

                        <div
                            id="userEditModal"
                            class="rp-modal"
                            aria-hidden="true"
                            data-open-on-load="{{ $editingUser ? '1' : '0' }}"
                            data-initial-name="{{ old('name', $editingUser?->name) }}"
                            data-initial-eid="{{ old('eid', $editingUser?->eid) }}"
                            data-initial-designation="{{ old('designation', $editingUser?->designation) }}"
                            data-initial-department="{{ old('department', $editingUser?->department) }}"
                            data-initial-role-id="{{ old('role_id', $editingUser?->role_id) }}"
                            data-initial-status="{{ old('status', $editingUser?->status) }}"
                            data-initial-update-url="{{ $editingUser ? route('admin.users.update', $editingUser) : '' }}"
                        >
                            <div class="rp-modal-card">
                                <h4 id="userEditModalTitle">Edit User</h4>

                                <form method="POST" id="userEditForm" action="{{ $editingUser ? route('admin.users.update', $editingUser) : '#' }}" class="admin-form-grid">
                                    @csrf

                                    <input type="text" id="userEditName" name="name" value="{{ old('name', $editingUser?->name) }}" class="admin-input" placeholder="Name" required>
                                    <input type="text" id="userEditEid" name="eid" value="{{ old('eid', $editingUser?->eid) }}" class="admin-input" placeholder="EID">
                                    <input type="text" id="userEditDesignation" name="designation" value="{{ old('designation', $editingUser?->designation) }}" class="admin-input" placeholder="Designation">
                                    <input type="text" id="userEditDepartment" name="department" value="{{ old('department', $editingUser?->department) }}" class="admin-input" placeholder="Department">

                                    <select id="userEditRoleId" name="role_id" class="admin-select" required>
                                        @foreach($roleOptions as $roleId => $roleLabel)
                                            <option value="{{ $roleId }}" @selected((int) old('role_id', $editingUser?->role_id) === (int) $roleId)>
                                                {{ $roleLabel }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <select id="userEditStatus" name="status" class="admin-select" required>
                                        <option value="Active" @selected(old('status', $editingUser?->status) === 'Active')>Active</option>
                                        <option value="Inactive" @selected(old('status', $editingUser?->status) === 'Inactive')>Inactive</option>
                                    </select>

                                    <div style="grid-column:1 / -1; display:flex; gap:10px;">
                                        <button type="submit" class="admin-content-action">Save Changes</button>
                                        <button type="button" class="admin-content-action" style="background:#475569;" onclick="closeUserEditModal()">Cancel</button>
                                    </div>
                                </form>

                                @if($errors->any() && $editingUser)
                                    <div class="admin-alert admin-alert-error" style="margin-top:10px;">
                                        @foreach($errors->all() as $error)
                                            <div>{{ $error }}</div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div id="passwordResetModal" class="rp-modal" aria-hidden="true">
                            <div class="rp-modal-card">
                                <h4 id="passwordResetModalTitle">Reset Password</h4>

                                <form method="POST" id="passwordResetForm" action="#" class="admin-form-grid">
                                    @csrf
                                    <input type="hidden" name="user_search" value="{{ $userFilters['search'] }}">

                                    <input type="password" id="resetPassword" name="password" class="admin-input" placeholder="New password (min 8 chars)" required>
                                    <input type="password" id="resetPasswordConfirmation" name="password_confirmation" class="admin-input" placeholder="Confirm new password" required>

                                    <div style="grid-column:1 / -1; display:flex; gap:10px;">
                                        <button type="submit" class="admin-content-action">Update Password</button>
                                        <button type="button" class="admin-content-action" style="background:#475569;" onclick="closePasswordResetModal()">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div id="deactivateConfirmModal" class="rp-modal" aria-hidden="true">
                            <div class="rp-modal-card">
                                <h4>Deactivate User</h4>
                                <p style="margin:0; color:#c8f9f4; font-size:1rem; line-height:1.5;">
                                    Are you sure you want to deactivate
                                    <strong id="deactivateConfirmName">this user</strong>?
                                    They will not be able to log in until reactivated.
                                </p>

                                <div class="rp-modal-actions" style="margin-top:16px;">
                                    <button type="button" class="rp-save-btn" id="deactivateConfirmProceed">Deactivate</button>
                                    <button type="button" class="rp-cancel-btn" id="deactivateConfirmCancel">Cancel</button>
                                </div>
                            </div>
                        </div>

                        <script>
                            function openUserEditModal(payload = {}) {
                                const modal = document.getElementById('userEditModal');
                                const form = document.getElementById('userEditForm');
                                const roleSelect = document.getElementById('userEditRoleId');
                                const statusSelect = document.getElementById('userEditStatus');

                                if (payload.updateUrl) {
                                    form.setAttribute('action', payload.updateUrl);
                                }

                                document.getElementById('userEditModalTitle').textContent = payload.name
                                    ? `Edit User: ${payload.name}`
                                    : 'Edit User';
                                document.getElementById('userEditName').value = payload.name || '';
                                document.getElementById('userEditEid').value = payload.eid || '';
                                document.getElementById('userEditDesignation').value = payload.designation || '';
                                document.getElementById('userEditDepartment').value = payload.department || '';

                                if (payload.roleId) {
                                    roleSelect.value = String(payload.roleId);
                                }

                                statusSelect.value = String(payload.status || 'Active').toLowerCase() === 'inactive'
                                    ? 'Inactive'
                                    : 'Active';

                                modal.classList.add('open');
                            }

                            function openUserEditModalFromButton(button) {
                                openUserEditModal({
                                    name: button.getAttribute('data-name') || '',
                                    eid: button.getAttribute('data-eid') || '',
                                    designation: button.getAttribute('data-designation') || '',
                                    department: button.getAttribute('data-department') || '',
                                    roleId: button.getAttribute('data-role-id') || '',
                                    status: button.getAttribute('data-status') || 'Active',
                                    updateUrl: button.getAttribute('data-update-url') || ''
                                });
                            }

                            function closeUserEditModal() {
                                document.getElementById('userEditModal').classList.remove('open');
                            }

                            function openPasswordResetModal(payload = {}) {
                                const modal = document.getElementById('passwordResetModal');
                                const form = document.getElementById('passwordResetForm');

                                if (payload.resetUrl) {
                                    form.setAttribute('action', payload.resetUrl);
                                }

                                document.getElementById('passwordResetModalTitle').textContent = payload.name
                                    ? `Reset Password: ${payload.name}`
                                    : 'Reset Password';
                                document.getElementById('resetPassword').value = '';
                                document.getElementById('resetPasswordConfirmation').value = '';
                                modal.classList.add('open');
                            }

                            function openPasswordResetModalFromButton(button) {
                                openPasswordResetModal({
                                    name: button.getAttribute('data-name') || '',
                                    resetUrl: button.getAttribute('data-reset-url') || ''
                                });
                            }

                            function closePasswordResetModal() {
                                document.getElementById('passwordResetModal').classList.remove('open');
                            }

                            let pendingDeactivateForm = null;

                            function openDeactivateConfirmModal(form) {
                                pendingDeactivateForm = form;

                                const modal = document.getElementById('deactivateConfirmModal');
                                const nameTarget = document.getElementById('deactivateConfirmName');
                                const userName = form.getAttribute('data-user-name') || 'this user';

                                nameTarget.textContent = userName;
                                modal.classList.add('open');
                            }

                            function closeDeactivateConfirmModal() {
                                const modal = document.getElementById('deactivateConfirmModal');
                                modal.classList.remove('open');
                                pendingDeactivateForm = null;
                            }

                            document.querySelectorAll('.js-deactivate-user-form').forEach(function (form) {
                                form.addEventListener('submit', function (event) {
                                    event.preventDefault();
                                    openDeactivateConfirmModal(form);
                                });
                            });

                            const deactivateConfirmModal = document.getElementById('deactivateConfirmModal');
                            const deactivateConfirmProceed = document.getElementById('deactivateConfirmProceed');
                            const deactivateConfirmCancel = document.getElementById('deactivateConfirmCancel');

                            if (deactivateConfirmProceed) {
                                deactivateConfirmProceed.addEventListener('click', function () {
                                    if (pendingDeactivateForm) {
                                        pendingDeactivateForm.submit();
                                    }
                                });
                            }

                            if (deactivateConfirmCancel) {
                                deactivateConfirmCancel.addEventListener('click', closeDeactivateConfirmModal);
                            }

                            if (deactivateConfirmModal) {
                                deactivateConfirmModal.addEventListener('click', function (event) {
                                    if (event.target === deactivateConfirmModal) {
                                        closeDeactivateConfirmModal();
                                    }
                                });
                            }

                            const userEditModal = document.getElementById('userEditModal');
                            if (userEditModal) {
                                userEditModal.addEventListener('click', function (event) {
                                    if (event.target === userEditModal) {
                                        closeUserEditModal();
                                    }
                                });
                            }

                            const passwordResetModal = document.getElementById('passwordResetModal');
                            if (passwordResetModal) {
                                passwordResetModal.addEventListener('click', function (event) {
                                    if (event.target === passwordResetModal) {
                                        closePasswordResetModal();
                                    }
                                });
                            }

                            const shouldOpenUserEditModal = document
                                .getElementById('userEditModal')
                                .getAttribute('data-open-on-load') === '1';

                            if (shouldOpenUserEditModal) {
                                openUserEditModal({
                                    name: userEditModal.getAttribute('data-initial-name') || '',
                                    eid: userEditModal.getAttribute('data-initial-eid') || '',
                                    designation: userEditModal.getAttribute('data-initial-designation') || '',
                                    department: userEditModal.getAttribute('data-initial-department') || '',
                                    roleId: userEditModal.getAttribute('data-initial-role-id') || '',
                                    status: userEditModal.getAttribute('data-initial-status') || 'Active',
                                    updateUrl: userEditModal.getAttribute('data-initial-update-url') || ''
                                });
                            }
                        </script>
                    @elseif($activeSection === 'attendance-logs')
                        <form method="GET" action="{{ route('admin.dashboard') }}" class="mb-6 admin-attendance-filter-grid">
                            <input type="hidden" name="section" value="attendance-logs">

                            <input
                                type="date"
                                name="from_date"
                                value="{{ $attendanceFilters['from_date'] }}"
                                class="admin-filter-input"
                            >
                            <input
                                type="date"
                                name="to_date"
                                value="{{ $attendanceFilters['to_date'] }}"
                                class="admin-filter-input"
                            >
                            <input
                                type="text"
                                name="employee"
                                value="{{ $attendanceFilters['employee'] }}"
                                placeholder="Search user, EID, or email"
                                class="admin-filter-input"
                            >
                            <button type="submit" class="admin-content-action justify-center">
                                Filter
                            </button>
                        </form>

                        <div class="admin-table-wrap admin-table-wrap-no-border mt-2">
                            <table class="admin-table admin-table-attendance">
                                <thead class="bg-white/10">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Date</th>
                                        <th class="px-4 py-3 font-semibold">Name</th>
                                        <th class="px-4 py-3 font-semibold">Designation</th>
                                        <th class="px-4 py-3 font-semibold">Department</th>
                                        <th class="px-4 py-3 font-semibold">Shift Type</th>
                                        <th class="px-4 py-3 font-semibold">Clock In</th>
                                        <th class="px-4 py-3 font-semibold">Checkin Status</th>
                                        <th class="px-4 py-3 font-semibold">Checkin Address</th>
                                        <th class="px-4 py-3 font-semibold">Clock Out</th>
                                        <th class="px-4 py-3 font-semibold">Checkout Status</th>
                                        <th class="px-4 py-3 font-semibold">Checkout Address</th>
                                        <th class="px-4 py-3 font-semibold">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($adminAttendances ?? collect()) as $attendance)
                                        <tr class="border-t border-white/10">
                                            <td class="px-4 py-3">{{ \Carbon\Carbon::parse($attendance->date)->format('M d, Y') }}</td>
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-white">{{ $attendance->user->name ?? 'Unknown' }}</div>
                                                <div class="text-xs text-white/65">{{ $attendance->user->eid ?? '-' }}</div>
                                            </td>
                                            <td class="px-4 py-3">{{ $attendance->user->designation ?? '-' }}</td>
                                            <td class="px-4 py-3">{{ $attendance->user->department ?? '-' }}</td>
                                            <td class="px-4 py-3">{{ $attendance->shift_name ?: '-' }}</td>
                                            <td class="px-4 py-3">{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('h:i A') : '--:--' }}</td>
                                            <td class="px-4 py-3">{{ $attendance->checkin_status ?? '-' }}</td>
                                            <td class="px-4 py-3">{{ $attendance->clockIn_address ?: '-' }}</td>
                                            <td class="px-4 py-3">{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('h:i A') : '--:--' }}</td>
                                            <td class="px-4 py-3">{{ $attendance->checkout_status ?? '-' }}</td>
                                            <td class="px-4 py-3">{{ $attendance->clockOut_address ?: '-' }}</td>
                                            <td class="px-4 py-3">{{ $attendance->computed_remarks ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="12" class="px-4 py-8 text-center text-white/65">No attendance records found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($adminAttendances)
                            <div class="mt-4">
                                {{ $adminAttendances->links() }}
                            </div>
                        @endif
                    @elseif($activeSection === 'leave-records')
                        <div class="overflow-x-auto rounded-lg">
                            <table class="min-w-full text-left text-sm text-white/90">
                                <thead class="bg-white/10">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Approved On</th>
                                        <th class="px-4 py-3 font-semibold">Employee</th>
                                        <th class="px-4 py-3 font-semibold">Department</th>
                                        <th class="px-4 py-3 font-semibold">Leave Type</th>
                                        <th class="px-4 py-3 font-semibold">From</th>
                                        <th class="px-4 py-3 font-semibold">To</th>
                                        <th class="px-4 py-3 font-semibold">Days</th>
                                        <th class="px-4 py-3 font-semibold">Reason</th>
                                        <th class="px-4 py-3 font-semibold">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($managedLeaveRecords ?? collect()) as $leave)
                                        <tr class="border-t border-white/10 align-top">
                                            <td class="px-4 py-3">{{ $leave->updated_at ? \Carbon\Carbon::parse($leave->updated_at)->format('M d, Y h:i A') : '-' }}</td>
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-white">{{ $leave->user?->name ?? 'Unknown' }}</div>
                                                <div class="text-xs text-white/65">{{ $leave->user?->eid ?? '-' }}</div>
                                            </td>
                                            <td class="px-4 py-3">{{ $leave->user?->department ?? '-' }}</td>
                                            <td class="px-4 py-3">{{ $leave->leaveType?->name ?? $leave->leave_type ?? '-' }}</td>
                                            <td class="px-4 py-3">{{ $leave->start_date ? \Carbon\Carbon::parse($leave->start_date)->format('M d, Y') : '-' }}</td>
                                            <td class="px-4 py-3">{{ $leave->end_date ? \Carbon\Carbon::parse($leave->end_date)->format('M d, Y') : '-' }}</td>
                                            <td class="px-4 py-3">{{ number_format((float) $leave->total_days, 2) }}</td>
                                            <td class="px-4 py-3 max-w-sm break-words">{{ $leave->reason ?: '-' }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex rounded-full bg-emerald-500/20 px-2.5 py-1 text-xs font-semibold text-emerald-200">
                                                    Approved by MS
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="px-4 py-8 text-center text-white/65">No leave requests approved by MS yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($managedLeaveRecords)
                            <div class="mt-4">
                                {{ $managedLeaveRecords->links() }}
                            </div>
                        @endif
                    @elseif($activeSection === 'staff-on-tour')
                        <div class="admin-table-wrap">
                            <table class="admin-table" style="min-width: 1100px;">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>EID</th>
                                        <th>Designation</th>
                                        <th>Department</th>
                                        <th>Place</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Total Days</th>
                                        <th>Purpose</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($managedStaffTours ?? collect()) as $tour)
                                        @php
                                            $tourStart = \Carbon\Carbon::parse($tour->start_date);
                                            $tourEnd = \Carbon\Carbon::parse($tour->end_date);
                                            $tourDays = $tourStart->diffInDays($tourEnd) + 1;
                                        @endphp
                                        <tr>
                                            <td>{{ $tour->user?->name ?? 'Unknown' }}</td>
                                            <td>{{ $tour->user?->eid ?? '-' }}</td>
                                            <td>{{ $tour->user?->designation ?? '-' }}</td>
                                            <td>{{ $tour->user?->department ?? '-' }}</td>
                                            <td>{{ $tour->place ?? '-' }}</td>
                                            <td>{{ $tourStart->format('M d, Y') }}</td>
                                            <td>{{ $tourEnd->format('M d, Y') }}</td>
                                            <td>{{ $tourDays }}</td>
                                            <td>{{ $tour->purpose ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center" style="color: rgba(255,255,255,0.75);">No staff tour records found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($managedStaffTours)
                            <div class="mt-4">
                                {{ $managedStaffTours->links() }}
                            </div>
                        @endif
                    @elseif($activeSection === 'reports')
                        <div class="flex flex-wrap items-center" style="column-gap: 18px; row-gap: 10px;">
                            <a href="{{ route('admin.dashboard', ['section' => 'reports', 'report' => 'bonus']) }}" class="admin-content-action">
                                Bonus
                            </a>
                            <a href="{{ route('admin.dashboard', ['section' => 'reports', 'report' => 'm-attendance']) }}" class="admin-content-action">
                                M-Attendance %
                            </a>
                        </div>

                        @if(request('report') === 'bonus')
                            <div class="mt-4 admin-table-wrap">
                                <table class="admin-table" style="min-width: 640px;">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Employee</th>
                                            <th>EID</th>
                                            <th>Department</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($bonusIpdEmployees as $employee)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $employee->name }}</td>
                                                <td>{{ $employee->eid ?: '-' }}</td>
                                                <td>{{ $employee->department ?: '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center">No IPD employees found with night-shift clock-in records.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @elseif(request('report') === 'm-attendance')
                            <p class="mt-4 text-white/80">M-Attendance % report module is ready to be connected.</p>
                        @endif
                    @elseif($activeSectionRoute)
                        <a href="{{ $activeSectionRoute }}" class="admin-content-action">
                            {{ $activeSectionRouteLabel }}
                        </a>
                    @else
                        <p>Pick a module and I will build it fully, not just style it.</p>
                    @endif
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection

