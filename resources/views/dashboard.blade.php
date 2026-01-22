@extends('layouts.app')

@section('title', 'Bảng điều khiển')

@section('content')
<div class="action-header">
    <h1 class="page-title">Tổng quan Hệ thống</h1>
</div>

<div class="dashboard-grid">
    <div class="card" style="border-left: 5px solid var(--primary-color);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="color: var(--text-sub); font-size: 0.9rem; font-weight: 600;">NHÂN VIÊN ĐANG LÀM</div>
                <div style="font-size: 2rem; font-weight: 800; color: var(--primary-dark);">{{ $totalEmployees }}</div>
            </div>
            <div style="width: 50px; height: 50px; background: rgba(36, 162, 92, 0.1); color: var(--primary-color); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                <i class="fas fa-user-tie"></i>
            </div>
        </div>
    </div>

    <div class="card" style="border-left: 5px solid #3b82f6;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="color: var(--text-sub); font-size: 0.9rem; font-weight: 600;">DỰ ÁN VẬN HÀNH</div>
                <div style="font-size: 2rem; font-weight: 800; color: #1d4ed8;">{{ $totalProjects }}</div>
            </div>
            <div style="width: 50px; height: 50px; background: rgba(59, 130, 246, 0.1); color: #3b82f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                <i class="fas fa-building"></i>
            </div>
        </div>
    </div>

    <div class="card" style="border-left: 5px solid #f59e0b;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="color: var(--text-sub); font-size: 0.9rem; font-weight: 600;">THIẾU ĐỊNH BIÊN</div>
                <div style="font-size: 2rem; font-weight: 800; color: #f59e0b;">{{ $totalShortage }}</div>
            </div>
            <div style="width: 50px; height: 50px; background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                <i class="fas fa-user-minus"></i>
            </div>
        </div>
    </div>

    <div class="card" style="border-left: 5px solid #8b5cf6;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="color: var(--text-sub); font-size: 0.9rem; font-weight: 600;">PHÒNG BAN</div>
                <div style="font-size: 2rem; font-weight: 800; color: #7c3aed;">{{ $totalDepartments }}</div>
            </div>
            <div style="width: 50px; height: 50px; background: rgba(139, 92, 246, 0.1); color: #8b5cf6; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                <i class="fas fa-sitemap"></i>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <h3>Tính năng đang phát triển</h3>
    <p>Chào mừng bạn đến với hệ thống quản lý HR phiên bản Laravel v1.0. Các module đang được chuyển đổi để mang lại trải nghiệm mượt mà nhất.</p>
</div>
@endsection
