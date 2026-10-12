@extends('layouts.app')

@section('styles')
    <style>
        /* ================================================
                   🎨 Premium Design System - Task Management
                   ================================================ */

        :root {
            --board-bg: #f0f4f8;
            --column-bg: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            --card-bg: #ffffff;
            --text-primary: #1a202c;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border-light: #e2e8f0;
            --border-lighter: #f1f5f9;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 12px 32px rgba(0, 0, 0, 0.08);
            --shadow-xl: 0 20px 50px rgba(0, 0, 0, 0.12);
            --radius-sm: 10px;
            --radius-md: 16px;
            --radius-lg: 22px;
            --radius-xl: 28px;
            --transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);

            /* Status Colors */
            --todo-gradient: linear-gradient(135deg, #94a3b8, #cbd5e1);
            --progress-gradient: linear-gradient(135deg, #3b82f6, #60a5fa);
            --completed-gradient: linear-gradient(135deg, #10b981, #34d399);
            --cancelled-gradient: linear-gradient(135deg, #ef4444, #f87171);

            /* Priority Colors */
            --low-color: #16a34a;
            --low-bg: #f0fdf4;
            --low-border: #bbf7d0;
            --medium-color: #2563eb;
            --medium-bg: #eff6ff;
            --medium-border: #bfdbfe;
            --high-color: #d97706;
            --high-bg: #fffbeb;
            --high-border: #fef3c7;
            --urgent-color: #e11d48;
            --urgent-bg: #fdf2f8;
            --urgent-border: #fbcfe8;
        }

        /* ================================================
                   📋 Kanban Board Container
                   ================================================ */
        .task-board {
            display: flex;
            gap: 1.25rem;
            overflow-x: auto;
            padding: 0.5rem 0.25rem 2rem 0.25rem;
            min-height: 620px;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
            scroll-behavior: smooth;
        }

        .task-board::-webkit-scrollbar {
            height: 6px;
        }

        .task-board::-webkit-scrollbar-track {
            background: transparent;
            border-radius: 10px;
        }

        .task-board::-webkit-scrollbar-thumb {
            background: linear-gradient(90deg, #cbd5e1, #94a3b8);
            border-radius: 10px;
        }

        .task-board::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(90deg, #94a3b8, #64748b);
        }

        /* ================================================
                   📊 Column Styles
                   ================================================ */
        .task-column {
            flex: 1;
            min-width: 310px;
            max-width: 360px;
            background: var(--column-bg);
            border-radius: var(--radius-lg);
            border: 1px solid rgba(226, 232, 240, 0.6);
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .task-column::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }

        .task-column[data-status="todo"]::before {
            background: var(--todo-gradient);
        }

        .task-column[data-status="in_progress"]::before {
            background: var(--progress-gradient);
        }

        .task-column[data-status="completed"]::before {
            background: var(--completed-gradient);
        }

        .task-column[data-status="cancelled"]::before {
            background: var(--cancelled-gradient);
        }

        .task-column:hover {
            box-shadow: var(--shadow-md);
            border-color: rgba(203, 213, 225, 0.8);
        }

        /* Column Header */
        .column-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 0.85rem;
            margin-bottom: 0.25rem;
            border-bottom: 2px dashed var(--border-lighter);
        }

        .column-title {
            font-weight: 800;
            font-size: 0.95rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.55rem;
            letter-spacing: -0.01em;
        }

        .column-title i {
            font-size: 1.1rem;
        }

        .column-count {
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            color: var(--text-secondary);
            font-size: 0.75rem;
            font-weight: 800;
            padding: 0.3rem 0.7rem;
            border-radius: 9999px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--border-light);
            min-width: 28px;
            text-align: center;
        }

        /* ================================================
                   🃏 Premium Task Card
                   ================================================ */
        .task-card {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            border: 1px solid rgba(226, 232, 240, 0.7);
            padding: 1.15rem;
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            position: relative;
            overflow: hidden;
        }

        .task-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3.5px;
            background: transparent;
            transition: var(--transition);
            border-radius: var(--radius-md) var(--radius-md) 0 0;
        }

        .task-card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0) 100%);
            opacity: 0;
            transition: var(--transition);
            pointer-events: none;
            border-radius: var(--radius-md);
        }

        .task-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(203, 213, 225, 0.9);
        }

        .task-card:hover::after {
            opacity: 1;
            background: linear-gradient(135deg, rgba(248, 250, 252, 0.3) 0%, rgba(255, 255, 255, 0) 100%);
        }

        /* Card Status Indicators */
        .task-card.status-todo::before {
            background: var(--todo-gradient);
        }

        .task-card.status-in_progress::before {
            background: var(--progress-gradient);
        }

        .task-card.status-completed::before {
            background: var(--completed-gradient);
        }

        .task-card.status-cancelled::before {
            background: var(--cancelled-gradient);
        }

        /* Card Title */
        .task-card .card-title {
            font-weight: 700;
            font-size: 0.92rem;
            color: var(--text-primary);
            line-height: 1.45;
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Card Description */
        .task-card .card-desc {
            font-size: 0.8rem;
            color: var(--text-muted);
            line-height: 1.5;
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Card Footer */
        .task-card .card-footer-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: auto;
            padding-top: 0.5rem;
            border-top: 1px solid var(--border-lighter);
        }

        /* ================================================
                   🏷️ Badges & Labels
                   ================================================ */
        .priority-badge {
            font-size: 0.7rem;
            font-weight: 800;
            padding: 0.3rem 0.6rem;
            border-radius: 8px;
            width: fit-content;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            transition: var(--transition);
        }

        .priority-low {
            background: var(--low-bg);
            color: var(--low-color);
            border: 1px solid var(--low-border);
        }

        .priority-medium {
            background: var(--medium-bg);
            color: var(--medium-color);
            border: 1px solid var(--medium-border);
        }

        .priority-high {
            background: var(--high-bg);
            color: var(--high-color);
            border: 1px solid var(--high-border);
        }

        .priority-urgent {
            background: var(--urgent-bg);
            color: var(--urgent-color);
            border: 1px solid var(--urgent-border);
            animation: pulse-urgent 2s infinite;
        }

        @keyframes pulse-urgent {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(225, 29, 72, 0.15);
            }

            50% {
                box-shadow: 0 0 0 4px rgba(225, 29, 72, 0.05);
            }
        }

        /* Scope Badges */
        .scope-badge {
            font-size: 0.68rem;
            font-weight: 700;
            padding: 0.25rem 0.55rem;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            letter-spacing: 0.2px;
        }

        .scope-project {
            background: rgba(59, 130, 246, 0.08);
            color: #2563eb;
            border: 1px solid rgba(59, 130, 246, 0.15);
        }

        .scope-value-chain {
            background: rgba(16, 185, 129, 0.08);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.15);
        }

        .scope-both {
            background: rgba(139, 92, 246, 0.08);
            color: #7c3aed;
            border: 1px solid rgba(139, 92, 246, 0.15);
        }

        .scope-general {
            background: rgba(100, 116, 139, 0.08);
            color: #475569;
            border: 1px solid rgba(100, 116, 139, 0.15);
        }

        /* Status Badges */
        .status-badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            white-space: nowrap;
        }

        .status-badge-todo {
            background: rgba(148, 163, 184, 0.1);
            color: #64748b;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        .status-badge-progress {
            background: rgba(59, 130, 246, 0.08);
            color: #2563eb;
            border: 1px solid rgba(59, 130, 246, 0.15);
        }

        .status-badge-completed {
            background: rgba(16, 185, 129, 0.08);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.15);
        }

        .status-badge-cancelled {
            background: rgba(239, 68, 68, 0.08);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.15);
        }

        /* ================================================
                   👤 User Avatars
                   ================================================ */
        .user-avatar-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary, #3b82f6), var(--primary-dark, #1e40af));
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.72rem;
            font-weight: 800;
            border: 2px solid white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            text-transform: uppercase;
            transition: var(--transition);
            position: relative;
        }

        .user-avatar-circle:hover {
            transform: scale(1.15);
            z-index: 2;
        }

        .avatar-stack {
            display: flex;
            align-items: center;
        }

        .avatar-stack .user-avatar-circle:not(:first-child) {
            margin-left: -8px;
        }

        .avatar-more {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
            color: var(--text-secondary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            font-weight: 800;
            border: 2px solid white;
            margin-left: -8px;
        }

        /* ================================================
                   📅 Meta Items
                   ================================================ */
        .card-meta-item {
            font-size: 0.78rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-weight: 600;
        }

        .card-meta-item i {
            font-size: 0.72rem;
        }

        .due-date-overdue {
            color: #dc2626;
            font-weight: 700;
        }

        .due-date-soon {
            color: #d97706;
            font-weight: 700;
        }

        /* ================================================
                   🖨️ Card Actions (Print Button)
                   ================================================ */
        .card-print-btn {
            opacity: 0;
            transition: var(--transition);
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(241, 245, 249, 0.8);
            color: var(--text-muted);
            font-size: 0.75rem;
            flex-shrink: 0;
            backdrop-filter: blur(4px);
        }

        .card-print-btn:hover {
            background: var(--primary, #3b82f6);
            color: white;
            transform: scale(1.1);
        }

        .task-card:hover .card-print-btn {
            opacity: 1;
        }

        /* ================================================
                   📊 Elegant Table (List View)
                   ================================================ */
        .elegant-table-wrapper {
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .elegant-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        .elegant-table thead th {
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            color: var(--text-secondary);
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 1rem 1.15rem;
            border-bottom: 2px solid var(--border-light);
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .elegant-table thead th:first-child {
            padding-right: 1.5rem;
        }

        .elegant-table tbody tr {
            transition: var(--transition);
            background: white;
        }

        .elegant-table tbody tr:hover {
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.02) 0%, rgba(255, 255, 255, 0) 100%);
            box-shadow: inset 3px 0 0 0 var(--primary, #3b82f6);
        }

        .elegant-table tbody tr:not(:last-child) td {
            border-bottom: 1px solid var(--border-lighter);
        }

        .elegant-table td {
            padding: 0.9rem 1.15rem;
            vertical-align: middle;
            font-size: 0.85rem;
        }

        .elegant-table td:first-child {
            padding-right: 1.5rem;
        }

        .table-task-title {
            font-weight: 700;
            color: var(--text-primary);
            font-size: 0.88rem;
            margin-bottom: 0.15rem;
        }

        .table-task-desc {
            color: var(--text-muted);
            font-size: 0.78rem;
            max-width: 280px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Table Action Buttons */
        .table-action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border-light);
            background: white;
            color: var(--text-muted);
            font-size: 0.78rem;
            transition: var(--transition);
            cursor: pointer;
        }

        .table-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .table-action-btn.btn-edit:hover {
            background: #fffbeb;
            border-color: #fde68a;
            color: #d97706;
        }

        .table-action-btn.btn-assign:hover {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #2563eb;
        }

        .table-action-btn.btn-print:hover {
            background: #f0fdf4;
            border-color: #bbf7d0;
            color: #16a34a;
        }

        /* ================================================
                   🔘 View Switcher
                   ================================================ */
        .view-switcher {
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
            border-radius: 12px;
            padding: 3px;
            display: inline-flex;
            gap: 2px;
        }

        .view-switcher .btn {
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.8rem;
            padding: 0.45rem 0.9rem;
            transition: var(--transition);
            color: var(--text-secondary);
            background: transparent;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .view-switcher .btn:hover {
            color: var(--text-primary);
            background: rgba(255, 255, 255, 0.5);
        }

        .view-switcher .btn.active {
            background: white;
            color: var(--primary, #3b82f6);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        /* ================================================
                   🔍 Filter Section
                   ================================================ */
        .filter-section {
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .filter-section .form-control,
        .filter-section .form-select {
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--border-light);
            font-size: 0.85rem;
            padding: 0.55rem 0.85rem;
            transition: var(--transition);
            background-color: #fafbfc;
        }

        .filter-section .form-control:focus,
        .filter-section .form-select:focus {
            border-color: var(--primary, #3b82f6);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.08);
            background-color: white;
        }

        .filter-section .input-group-text {
            border-radius: var(--radius-sm) 0 0 var(--radius-sm);
            border: 1.5px solid var(--border-light);
            border-right: none;
            background: #fafbfc;
        }

        .filter-section .input-group .form-control {
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
            border-left: none;
        }

        /* Reset Button */
        .btn-reset {
            width: 38px;
            height: 38px;
            border-radius: var(--radius-sm);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1.5px solid var(--border-light);
            background: white;
            color: var(--text-muted);
            transition: var(--transition);
        }

        .btn-reset:hover {
            background: #fef2f2;
            border-color: #fecaca;
            color: #dc2626;
            transform: rotate(180deg);
        }

        /* Print List Button */
        .btn-print-list {
            width: 38px;
            height: 38px;
            border-radius: var(--radius-sm);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1.5px solid var(--border-light);
            background: white;
            color: var(--text-muted);
            transition: var(--transition);
        }

        .btn-print-list:hover {
            background: #f0fdf4;
            border-color: #bbf7d0;
            color: #16a34a;
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        /* ================================================
                   ✨ Animations
                   ================================================ */
        .animate-fade {
            animation: fadeInUp 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-stagger>* {
            animation: fadeInUp 0.4s cubic-bezier(0.4, 0, 0.2, 1) backwards;
        }

        .animate-stagger>*:nth-child(1) {
            animation-delay: 0.05s;
        }

        .animate-stagger>*:nth-child(2) {
            animation-delay: 0.1s;
        }

        .animate-stagger>*:nth-child(3) {
            animation-delay: 0.15s;
        }

        .animate-stagger>*:nth-child(4) {
            animation-delay: 0.2s;
        }

        .animate-stagger>*:nth-child(5) {
            animation-delay: 0.25s;
        }

        .animate-stagger>*:nth-child(6) {
            animation-delay: 0.3s;
        }

        .animate-stagger>*:nth-child(7) {
            animation-delay: 0.35s;
        }

        .animate-stagger>*:nth-child(8) {
            animation-delay: 0.4s;
        }

        /* ================================================
                   🪟 Modal Enhancements
                   ================================================ */
        .modal-content.premium-modal {
            border: none;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
        }

        .premium-modal .modal-header {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: none;
            padding: 1.25rem 1.75rem;
        }

        .premium-modal .modal-title {
            font-weight: 800;
            font-size: 1.05rem;
            color: #92400e;
        }

        .premium-modal .modal-body {
            padding: 1.5rem 1.75rem;
        }

        .premium-modal .form-label {
            font-weight: 700;
            font-size: 0.82rem;
            color: #475569;
            margin-bottom: 0.4rem;
        }

        .premium-modal .form-control,
        .premium-modal .form-select {
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--border-light);
            font-size: 0.88rem;
            padding: 0.6rem 0.9rem;
            transition: var(--transition);
        }

        .premium-modal .form-control:focus,
        .premium-modal .form-select:focus {
            border-color: var(--primary, #3b82f6);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.08);
        }

        .premium-modal .modal-footer {
            border: none;
            padding: 1rem 1.75rem 1.5rem;
        }

        .premium-modal .btn-save {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 800;
            padding: 0.6rem 1.5rem;
            color: white;
            transition: var(--transition);
        }

        .premium-modal .btn-save:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(217, 119, 6, 0.3);
        }

        /* ================================================
                   📱 Empty State
                   ================================================ */
        .empty-state {
            text-align: center;
            padding: 2.5rem 1.5rem;
            border: 2px dashed var(--border-light);
            border-radius: var(--radius-md);
            background: rgba(248, 250, 252, 0.5);
        }

        .empty-state i {
            font-size: 2rem;
            color: #cbd5e1;
            margin-bottom: 0.75rem;
            display: block;
        }

        .empty-state span {
            font-size: 0.82rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        /* ================================================
                   📱 Responsive Adjustments
                   ================================================ */
        @media (max-width: 768px) {
            .task-board {
                gap: 1rem;
                padding: 0.25rem 0.25rem 1.5rem;
            }

            .task-column {
                min-width: 280px;
            }

            .filter-section {
                padding: 1rem;
            }
        }

        /* ================================================
                   🖨️ Print Styles
                   ================================================ */
        @media print {
            .task-board {
                display: block;
            }

            .task-column {
                break-inside: avoid;
                page-break-inside: avoid;
                margin-bottom: 1rem;
                box-shadow: none;
                border: 1px solid #ddd;
            }

            .task-card {
                box-shadow: none;
                border: 1px solid #eee;
            }

            .card-print-btn,
            .view-switcher,
            .filter-section {
                display: none !important;
            }
        }
    </style>
@endsection

@section('content')
    <x-index-page title="نظام المهام العام" icon="tasks" :paginator="$tasks">
        <x-slot name="headerActions">
            @can('create', \App\Models\Task::class)
                <a href="{{ route('tasks.create') }}" class="btn btn-primary shadow-sm fw-bold px-4 py-2"
                    style="border-radius: 12px; font-size: 0.88rem;">
                    <i class="fas fa-plus me-2"></i>إضافة مهمة جديدة
                </a>
            @endcan
        </x-slot>

        {{-- Breadcrumb --}}
        <x-slot name="breadcrumb">
            <nav aria-label="breadcrumb" class="mb-0">
                <ol class="breadcrumb mb-0" style="font-size: 0.82rem;">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}" class="text-muted text-decoration-none">
                            <i class="fas fa-home me-1"></i>الرئيسية
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('projects.index') }}" class="text-muted text-decoration-none">المشاريع</a>
                    </li>
                    <li class="breadcrumb-item active text-primary fw-bold">إدارة المهام</li>
                </ol>
            </nav>
        </x-slot>

        {{-- Filters --}}
        <x-slot name="filters">
            <div class="filter-section">
                <form method="GET" action="{{ route('tasks.index') }}" class="row g-3 align-items-end">
                    {{-- Search --}}
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-search me-1"></i>بحث
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search text-muted"
                                    style="font-size: 0.8rem;"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="بحث عن مهمة..."
                                value="{{ request('search') }}">
                        </div>
                    </div>

                    {{-- Status --}}
                    <div class="col-md-2">
                        <label class="form-label fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-flag me-1"></i>الحالة
                        </label>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">كل الحالات</option>
                            <option value="todo" {{ request('status') === 'todo' ? 'selected' : '' }}>⏳ قيد الانتظار</option>
                            <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>🔄 قيد
                                التنفيذ</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>✅ مكتملة
                            </option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>❌ ملغاة
                            </option>
                        </select>
                    </div>

                    {{-- Priority --}}
                    <div class="col-md-2">
                        <label class="form-label fw-bold text-muted" style="font-size: 0.75rem;">تقارير الإنجاز</label>
                        <select name="report_status" class="form-select" onchange="this.form.submit()">
                            <option value="">كل حالات التقارير</option>
                            <option value="overdue" @selected(request('report_status') === 'overdue')>تقارير مفقودة ومتأخرة</option>
                        </select>
                    </div>

                    {{-- Priority --}}
                    <div class="col-md-2">
                        <label class="form-label fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-exclamation-triangle me-1"></i>الأولوية
                        </label>
                        <select name="priority" class="form-select" onchange="this.form.submit()">
                            <option value="">كل الأولويات</option>
                            <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>🟢 منخفضة</option>
                            <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>🔵 متوسطة</option>
                            <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>🟠 عالية</option>
                            <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>🔴 عاجلة</option>
                        </select>
                    </div>

                    {{-- Scope --}}
                    <div class="col-md-2">
                        <label class="form-label fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-layer-group me-1"></i>النطاق
                        </label>
                        <select name="scope" class="form-select" onchange="this.form.submit()">
                            <option value="">كل النطاقات</option>
                            <option value="project_only" {{ request('scope') === 'project_only' ? 'selected' : '' }}>مشروع
                            </option>
                            <option value="value_chain_only" {{ request('scope') === 'value_chain_only' ? 'selected' : '' }}>
                                سلسلة القيمة</option>
                            <option value="both" {{ request('scope') === 'both' ? 'selected' : '' }}>مشروع وسلسلة قيمة
                            </option>
                            <option value="general" {{ request('scope') === 'general' ? 'selected' : '' }}>عامة</option>
                        </select>
                    </div>

                    {{-- Assignees --}}
                    <div class="col-md-2">
                        <label class="form-label fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-users me-1"></i>المنفذ
                        </label>
                        <select name="assigned_to[]" multiple class="form-select select2" onchange="this.form.submit()">
                            <option value="">كل المنفذين</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ in_array($user->id, (array) request('assigned_to', [])) ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Entity (الجهة) --}}
                    <div class="col-md-2">
                        <label class="form-label fw-bold text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-building me-1"></i>الجهة المرتبطة
                        </label>
                        <select name="project_entities_id" class="form-select" onchange="this.form.submit()">
                            <option value="">كل الجهات</option>
                            @foreach($projectEntities as $entity)
                                <option value="{{ $entity->id }}" {{ request('project_entities_id') == $entity->id ? 'selected' : '' }}>
                                    {{ $entity->entity_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Actions --}}
                    <div class="col-md-1 d-flex gap-2 justify-content-end align-items-end pb-1">
                        <div class="view-switcher" role="group">
                            <button type="button" class="btn active" id="btn-kanban" onclick="switchView('kanban')">
                                <i class="fas fa-columns"></i>
                            </button>
                            <button type="button" class="btn" id="btn-list" onclick="switchView('list')">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Extra Actions Row --}}
                    <div class="col-12 d-flex gap-2 justify-content-end mt-1">
                        @if(request()->anyFilled(['search', 'status', 'priority', 'assigned_to', 'scope']))
                            <a href="{{ route('tasks.index') }}" class="btn-reset" title="إعادة تعيين الفلاتر">
                                <i class="fas fa-sync-alt"></i>
                            </a>
                        @endif
                        <a href="{{ route('tasks.print_index') }}?{{ http_build_query(request()->query()) }}"
                            target="_blank" class="btn-print-list" title="طباعة القائمة">
                            <i class="fas fa-print"></i>
                        </a>
                    </div>
                </form>
            </div>
        </x-slot>

        {{-- ================================================ --}}
        {{-- 🎯 KANBAN VIEW --}}
        {{-- ================================================ --}}
        <div id="view-kanban" class="animate-fade">
            <div class="task-board">

                {{-- ===== TODO Column ===== --}}
                <div class="task-column" data-status="todo">
                    <div class="column-header">
                        <span class="column-title">
                            <i class="far fa-circle text-secondary"></i>
                            قيد الانتظار
                        </span>
                        <span class="column-count">{{ $tasks->where('status', 'todo')->count() }}</span>
                    </div>
                    <div class="animate-stagger">
                        @forelse($tasks->where('status', 'todo') as $task)
                            @include('projects.tasks.partials.task_card', ['task' => $task])
                        @empty
                            <div class="empty-state">
                                <i class="far fa-folder-open"></i>
                                <span>لا توجد مهام</span>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- ===== IN PROGRESS Column ===== --}}
                <div class="task-column" data-status="in_progress">
                    <div class="column-header">
                        <span class="column-title">
                            <i class="fas fa-spinner text-primary fa-spin"></i>
                            قيد التنفيذ
                        </span>
                        <span class="column-count">{{ $tasks->where('status', 'in_progress')->count() }}</span>
                    </div>
                    <div class="animate-stagger">
                        @forelse($tasks->where('status', 'in_progress') as $task)
                            @include('projects.tasks.partials.task_card', ['task' => $task])
                        @empty
                            <div class="empty-state">
                                <i class="far fa-folder-open"></i>
                                <span>لا توجد مهام</span>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- ===== COMPLETED Column ===== --}}
                <div class="task-column" data-status="completed">
                    <div class="column-header">
                        <span class="column-title">
                            <i class="far fa-check-circle text-success"></i>
                            مكتملة
                        </span>
                        <span class="column-count">{{ $tasks->where('status', 'completed')->count() }}</span>
                    </div>
                    <div class="animate-stagger">
                        @forelse($tasks->where('status', 'completed') as $task)
                            @include('projects.tasks.partials.task_card', ['task' => $task])
                        @empty
                            <div class="empty-state">
                                <i class="far fa-folder-open"></i>
                                <span>لا توجد مهام</span>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- ===== CANCELLED Column ===== --}}
                <div class="task-column" data-status="cancelled">
                    <div class="column-header">
                        <span class="column-title">
                            <i class="far fa-times-circle text-danger"></i>
                            ملغاة
                        </span>
                        <span class="column-count">{{ $tasks->where('status', 'cancelled')->count() }}</span>
                    </div>
                    <div class="animate-stagger">
                        @forelse($tasks->where('status', 'cancelled') as $task)
                            @include('projects.tasks.partials.task_card', ['task' => $task])
                        @empty
                            <div class="empty-state">
                                <i class="far fa-folder-open"></i>
                                <span>لا توجد مهام</span>
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>

        {{-- ================================================ --}}
        {{-- 📋 LIST VIEW --}}
        {{-- ================================================ --}}
        <div id="view-list" class="d-none animate-fade">
            <div class="elegant-table-wrapper">
                <div class="table-responsive">
                    <table class="elegant-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="px-4 py-3">المهمة</th>
                                <th>الحالة</th>
                                <th>الأولوية</th>
                                <th>النطاق</th>
                                <th>الجهة المرتبطة</th>
                                <th>النشاط</th>
                                <th>الإجراء</th>
                                <th>المسند إليه</th>
                                <th>الاستحقاق</th>
                                <th class="text-center">العمليات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tasks as $task)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="table-task-title">{{ $task->title }}</div>
                                        <div class="table-task-desc">
                                            {{ $task->description ?? 'بدون وصف' }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($task->status === 'todo')
                                            <span class="status-badge status-badge-todo">
                                                <i class="far fa-circle"></i>انتظار
                                            </span>
                                        @elseif($task->status === 'in_progress')
                                            <span class="status-badge status-badge-progress">
                                                <i class="fas fa-spinner fa-spin"></i>تنفيذ
                                            </span>
                                        @elseif($task->status === 'completed')
                                            <span class="status-badge status-badge-completed">
                                                <i class="far fa-check-circle"></i>مكتملة
                                            </span>
                                        @elseif($task->status === 'cancelled')
                                            <span class="status-badge status-badge-cancelled">
                                                <i class="far fa-times-circle"></i>ملغاة
                                            </span>
                                        @endif
                                        @if($task->isStandaloneGeneralTask() && $task->due_date && $task->due_date->lt(now()->startOfDay()) && (($task->required_report_count ?? 0) > ($task->submitted_report_count ?? 0)))
                                            <span class="badge bg-danger d-block mt-1">متأخرة — تقرير الإنجاز مفقود</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="priority-badge priority-{{ $task->priority }}">
                                            {{ $task->priority === 'low' ? 'منخفضة' : ($task->priority === 'medium' ? 'متوسطة' : ($task->priority === 'high' ? 'عالية' : 'عاجلة')) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($task->project_id && $task->value_chain_id)
                                            <span class="scope-badge scope-both">
                                                <i class="fas fa-link"></i>مشروع وسلسلة
                                            </span>
                                        @elseif($task->project_id)
                                            <span class="scope-badge scope-project">
                                                <i class="fas fa-project-diagram"></i>مشروع
                                            </span>
                                        @elseif($task->value_chain_id)
                                            <span class="scope-badge scope-value-chain">
                                                <i class="fas fa-link"></i>سلسلة قيمة
                                            </span>
                                        @else
                                            <span class="scope-badge scope-general">
                                                <i class="fas fa-globe"></i>عامة
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($task->projectEntity)
                                            <span class="text-muted small fw-semibold">
                                                <i class="fas fa-building me-1 text-primary" style="font-size: 0.7rem;"></i>
                                                {{ Str::limit($task->projectEntity->entity_name, 20) }}
                                            </span>
                                        @else
                                            <span class="text-muted" style="font-size: 0.8rem;">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($task->linked_activity_name)
                                            <span class="small fw-semibold">
                                                @if($task->activity_type === 'preliminary')
                                                    <span class="scope-badge scope-project">
                                                        <i class="fas fa-clipboard-list"></i>
                                                        {{ Str::limit($task->linked_activity_name, 18) }}
                                                    </span>
                                                @else
                                                    <span class="scope-badge scope-value-chain">
                                                        <i class="fas fa-tasks"></i>
                                                        {{ Str::limit($task->linked_activity_name, 18) }}
                                                    </span>
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-muted" style="font-size: 0.8rem;">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($task->linked_procedure_name)
                                            <span class="text-muted small fw-semibold" title="{{ $task->linked_procedure_name }}">
                                                <i class="fas fa-link text-info me-1" style="font-size: 0.7rem;"></i>
                                                {{ Str::limit($task->linked_procedure_name, 25) }}
                                            </span>
                                        @else
                                            <span class="text-muted" style="font-size: 0.8rem;">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($task->assignees->isNotEmpty())
                                            <div class="avatar-stack">
                                                @foreach($task->assignees->take(3) as $assignee)
                                                    <span class="user-avatar-circle" title="{{ $assignee->name }}">
                                                        {{ mb_substr($assignee->name, 0, 1) }}
                                                    </span>
                                                @endforeach
                                                @if($task->assignees->count() > 3)
                                                    <span class="avatar-more">
                                                        +{{ $task->assignees->count() - 3 }}
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted" style="font-size: 0.8rem;">غير مسندة</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($task->due_date)
                                            @php
                                                $daysLeft = now()->diffInDays($task->due_date, false);
                                            @endphp
                                            <span
                                                class="card-meta-item {{ $daysLeft < 0 ? 'due-date-overdue' : ($daysLeft <= 3 ? 'due-date-soon' : '') }}">
                                                <i class="far fa-calendar-alt"></i>
                                                {{ $task->due_date->format('Y-m-d') }}
                                            </span>
                                        @else
                                            <span class="text-muted" style="font-size: 0.8rem;">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="{{ $task->project_id ? route('projects.tasks.show', [$task->project_id, $task->id]) : route('tasks.show', $task->id) }}"
                                                class="table-action-btn btn-view" title="عرض تفاصيل المهمة" aria-label="عرض تفاصيل المهمة">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @can('update', $task)
                                                <button type="button" class="table-action-btn btn-edit" title="تعديل"
                                                    onclick="editTaskModal({{ json_encode($task) }})">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <button type="button" class="table-action-btn btn-assign" title="تكليف"
                                                    onclick="editTaskModal({{ json_encode($task) }})">
                                                    <i class="fas fa-user-plus"></i>
                                                </button>
                                            @endcan
                                            @can('view', $task)
                                                <a href="{{ $task->project_id ? route('projects.tasks.print', [$task->project_id, $task->id]) : route('tasks.print', $task->id) }}"
                                                    target="_blank" class="table-action-btn btn-print" title="طباعة">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-5">
                                        <div class="empty-state mx-auto" style="max-width: 300px;">
                                            <i class="fas fa-tasks"></i>
                                            <span>لا توجد أي مهام مسجلة</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </x-index-page>

    {{-- ================================================ --}}
    {{-- 🪟 EDIT TASK MODAL --}}
    {{-- ================================================ --}}
    @can('create', \App\Models\Task::class)
        <div class="modal fade" id="editTaskModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <form id="editTaskForm" method="POST" class="modal-content premium-modal">
                    @csrf
                    @method('PUT')

                    {{-- Header --}}
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-pen-to-square me-2"></i>تعديل المهمة
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    {{-- Body --}}
                    <div class="modal-body">
                        {{-- Title --}}
                        <div class="mb-3">
                            <label class="form-label">عنوان المهمة </label>
                            <input type="text" name="title" id="edit-title" class="form-control"
                                placeholder="أدخل عنوان المهمة...">
                        </div>

                        {{-- Description --}}
                        <div class="mb-3">
                            <label class="form-label">الوصف والتفاصيل</label>
                            <textarea name="description" id="edit-description" class="form-control" rows="3"
                                placeholder="أضف وصفاً تفصيلياً للمهمة..."></textarea>
                        </div>

                        {{-- Status & Priority --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">الحالة </label>
                                <select name="status" id="edit-status" class="form-select">
                                    <option value="todo">⏳ قيد الانتظار</option>
                                    <option value="in_progress">🔄 قيد التنفيذ</option>
                                    <option value="completed">✅ مكتملة</option>
                                    <option value="cancelled">❌ ملغاة</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الأولوية </label>
                                <select name="priority" id="edit-priority" class="form-select">
                                    <option value="low">🟢 منخفضة</option>
                                    <option value="medium">🔵 متوسطة</option>
                                    <option value="high">🟠 عالية</option>
                                    <option value="urgent">🔴 عاجلة</option>
                                </select>
                            </div>
                        </div>

                        {{-- Entity --}}
                        <div class="mb-3">
                            <label class="form-label">الجهة المرتبطة بالطلب</label>
                            <select name="project_entities_id" id="edit-project_entities_id" class="form-select">
                                <option value="">اختر جهة (اختياري)</option>
                                @foreach($projectEntities ?? [] as $entity)
                                    <option value="{{ $entity->id }}">{{ $entity->entity_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Activities Toggle --}}
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="edit_is_within_activities"
                                    name="is_within_activities" value="1" style="cursor: pointer;">
                                <label class="form-check-label fw-bold" for="edit_is_within_activities"
                                    style="cursor: pointer; font-size: 0.85rem;">
                                    هل هي ضمن الأنشطة والإجراءات؟
                                </label>
                            </div>
                        </div>

                        {{-- Activities Section (Hidden by default) --}}
                        <div id="edit_activities_section" class="d-none mb-3 p-3 rounded-3"
                            style="background: #f8fafc; border: 1px dashed #e2e8f0;">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">النشاط</label>
                                    <select id="edit_activity_select" name="activity_id" class="form-select">
                                        <option value="">-- اختر النشاط --</option>
                                    </select>
                                    <input type="hidden" id="edit_activity_type" name="activity_type" value="">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">الإجراء / الفعالية</label>
                                    <select id="edit_procedure_select" name="procedure_id" class="form-select">
                                        <option value="">-- اختر الإجراء --</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Executive Action --}}
                        <div class="mb-3" id="edit_executive_action_wrapper">
                            <label class="form-label">الإجراء التنفيذي المرتبط (سريع)</label>
                            <select name="executive_activity_action_id" id="edit-executive_activity_action_id"
                                class="form-select">
                                <option value="">بدون ربط بإجراء تنفيذي</option>
                                @foreach($executiveActions ?? [] as $action)
                                    <option value="{{ $action->id }}">{{ $action->action }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Assignees & Due Date --}}
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">إسناد إلى مستخدم</label>
                                @can('task.assign')
                                <select name="assigned_to[]" multiple id="edit-assigned_to" class="form-select">
                                    <option value="">اختر مستخدماً (اختياري)</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                @endcan
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">تاريخ الاستحقاق</label>
                                <input type="date" name="due_date" id="edit-due_date" class="form-control">
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal"
                            style="border-radius: 10px; font-weight: 700;">
                            إلغاء
                        </button>
                        <button type="submit" class="btn btn-save">
                            <i class="fas fa-check me-1"></i>حفظ التغييرات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

@endsection

@section('scripts')
    <script>
        const activitiesUrl = "{{ route('tasks.activities') }}";
        const proceduresUrl = "{{ route('tasks.procedures') }}";
        const projectId = null;

        // ================================================
        // View Switching
        // ================================================
        function switchView(view) {
            const kanbanView = document.getElementById('view-kanban');
            const listView = document.getElementById('view-list');
            const btnKanban = document.getElementById('btn-kanban');
            const btnList = document.getElementById('btn-list');

            if (view === 'kanban') {
                kanbanView.classList.remove('d-none');
                listView.classList.add('d-none');
                btnKanban.classList.add('active');
                btnList.classList.remove('active');
                localStorage.setItem('task_view_mode', 'kanban');
            } else {
                kanbanView.classList.add('d-none');
                listView.classList.remove('d-none');
                btnKanban.classList.remove('active');
                btnList.classList.add('active');
                localStorage.setItem('task_view_mode', 'list');
            }
        }

        // ================================================
        // Edit Modal Dynamic Handling
        // ================================================
        const editToggle = document.getElementById('edit_is_within_activities');
        const editSection = document.getElementById('edit_activities_section');
        const editExecWrapper = document.getElementById('edit_executive_action_wrapper');
        const editActivitySelect = document.getElementById('edit_activity_select');
        const editActivityType = document.getElementById('edit_activity_type');
        const editProcedureSelect = document.getElementById('edit_procedure_select');

        if (editToggle) {
            editToggle.addEventListener('change', function () {
                if (this.checked) {
                    editSection.classList.remove('d-none');
                    editExecWrapper.classList.add('d-none');
                    loadActivities(projectId, editActivitySelect, editActivityType, editProcedureSelect);
                } else {
                    editSection.classList.add('d-none');
                    editExecWrapper.classList.remove('d-none');
                    editActivitySelect.innerHTML = '<option value="">-- اختر النشاط --</option>';
                    editProcedureSelect.innerHTML = '<option value="">-- اختر الإجراء --</option>';
                    editActivityType.value = '';
                }
            });
        }

        if (editActivitySelect) {
            editActivitySelect.addEventListener('change', function () {
                const selected = this.options[this.selectedIndex];
                const type = selected?.dataset?.type || '';
                editActivityType.value = type;
                loadProcedures(this.value, type, editProcedureSelect);
            });
        }

        // ================================================
        // Load Activities & Procedures (AJAX)
        // ================================================
        function loadActivities(projId, actSelect, actTypeInput, procSelect, selectedActId = null, selectedProcId = null) {
            if (!actSelect) return;
            actSelect.innerHTML = '<option value="">-- اختر النشاط --</option>';
            if (procSelect) procSelect.innerHTML = '<option value="">-- اختر الإجراء --</option>';
            if (!projId) return;

            fetch(`${activitiesUrl}?project_id=${projId}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(r => r.json())
                .then(data => {
                    data.forEach(item => {
                        const opt = document.createElement('option');
                        opt.value = item.id;
                        opt.textContent = item.label;
                        opt.dataset.type = item.type;
                        if (selectedActId && selectedActId == item.id && item.type === actTypeInput.value) {
                            opt.selected = true;
                        }
                        actSelect.appendChild(opt);
                    });
                    if (selectedActId && procSelect) {
                        loadProcedures(selectedActId, actTypeInput.value, procSelect, selectedProcId);
                    }
                })
                .catch(() => { });
        }

        function loadProcedures(activityId, activityType, procSelect, selectedProcId = null) {
            if (!procSelect) return;
            procSelect.innerHTML = '<option value="">-- اختر الإجراء --</option>';
            if (!activityId || !activityType) return;

            fetch(`${proceduresUrl}?activity_id=${activityId}&activity_type=${activityType}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(r => r.json())
                .then(data => {
                    data.forEach(item => {
                        const opt = document.createElement('option');
                        opt.value = item.id;
                        opt.textContent = item.name;
                        if (selectedProcId && selectedProcId == item.id) {
                            opt.selected = true;
                        }
                        procSelect.appendChild(opt);
                    });
                })
                .catch(() => { });
        }

        // ================================================
        // Open Edit Modal
        // ================================================
        function editTaskModal(task) {
            document.getElementById('edit-title').value = task.title;
            document.getElementById('edit-description').value = task.description || '';
            document.getElementById('edit-status').value = task.status;
            document.getElementById('edit-priority').value = task.priority;
            document.getElementById('edit-project_entities_id').value = task.project_entities_id || '';
            document.getElementById('edit-executive_activity_action_id').value = task.executive_activity_action_id || '';

            // Assignees
            const assignedSelect = document.getElementById('edit-assigned_to');
            if (assignedSelect && task.assignees) {
                const assignedIds = task.assignees.map(a => a.id);
                Array.from(assignedSelect.options).forEach(opt => {
                    opt.selected = assignedIds.includes(parseInt(opt.value));
                });
                $(assignedSelect).trigger('change');
            }

            document.getElementById('edit-due_date').value = task.due_date || '';

            const editToggle = document.getElementById('edit_is_within_activities');
            const editSection = document.getElementById('edit_activities_section');
            const editExecWrapper = document.getElementById('edit_executive_action_wrapper');
            const editActivityType = document.getElementById('edit_activity_type');

            if (task.is_within_activities) {
                editToggle.checked = true;
                editSection.classList.remove('d-none');
                editExecWrapper.classList.add('d-none');
                editActivityType.value = task.activity_type || '';
                loadActivities(projectId, editActivitySelect, editActivityType, editProcedureSelect, task.activity_id, task.procedure_id);
            } else {
                editToggle.checked = false;
                editSection.classList.add('d-none');
                editExecWrapper.classList.remove('d-none');
                editActivitySelect.innerHTML = '<option value="">-- اختر النشاط --</option>';
                editProcedureSelect.innerHTML = '<option value="">-- اختر الإجراء --</option>';
                editActivityType.value = '';
            }

            const form = document.getElementById('editTaskForm');
            form.action = `/tasks/${task.id}`;

            const modal = new bootstrap.Modal(document.getElementById('editTaskModal'));
            modal.show();
        }

        // ================================================
        // Initialize View on Load
        // ================================================
        document.addEventListener('DOMContentLoaded', function () {
            const savedView = localStorage.getItem('task_view_mode') || 'kanban';
            switchView(savedView);
        });
    </script>
@endsection
