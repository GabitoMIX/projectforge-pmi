-- Estructura inicial completa para una instalacion nueva de ProjectForge PMI.
-- Ejecutar antes de plan_calidad_seed.sql.

CREATE DATABASE IF NOT EXISTS plan_calidad_mvp
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE plan_calidad_mvp;

DROP TABLE IF EXISTS quality_normative_documents;
DROP TABLE IF EXISTS quality_organization_items;
DROP TABLE IF EXISTS quality_roles;
DROP TABLE IF EXISTS quality_activity_matrix;
DROP TABLE IF EXISTS process_improvement_steps;
DROP TABLE IF EXISTS quality_baselines;
DROP TABLE IF EXISTS quality_plan_versions;
DROP TABLE IF EXISTS quality_plans;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','user') NOT NULL DEFAULT 'user',
    status ENUM('pending','active','disabled') NOT NULL DEFAULT 'pending',
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    approved_at TIMESTAMP NULL,
    approved_by BIGINT UNSIGNED NULL,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE quality_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(255) NOT NULL,
    project_acronym VARCHAR(50) NOT NULL,
    header_left_logo VARCHAR(255) NULL,
    header_left_title VARCHAR(160) NULL,
    header_left_subtitle VARCHAR(160) NULL,
    header_right_logo VARCHAR(255) NULL,
    header_right_title VARCHAR(160) NULL,
    header_right_subtitle VARCHAR(160) NULL,
    document_code VARCHAR(100) NULL,
    footer_note TEXT NULL,
    quality_policy TEXT NOT NULL,
    assurance_approach TEXT NULL,
    control_approach TEXT NULL,
    improvement_approach_intro TEXT NULL,
    status ENUM('draft','in_progress','finalized') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE quality_plan_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quality_plan_id BIGINT UNSIGNED NOT NULL,
    version_number VARCHAR(20) NOT NULL,
    made_by VARCHAR(150) NULL,
    reviewed_by VARCHAR(150) NULL,
    approved_by VARCHAR(150) NULL,
    version_date DATE NULL,
    reason VARCHAR(255) NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quality_plan_id) REFERENCES quality_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE quality_baselines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quality_plan_id BIGINT UNSIGNED NOT NULL,
    quality_factor VARCHAR(255) NOT NULL,
    quality_objective VARCHAR(255) NOT NULL,
    metric TEXT NOT NULL,
    measurement_frequency TEXT NOT NULL,
    report_frequency TEXT NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (quality_plan_id) REFERENCES quality_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE process_improvement_steps (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quality_plan_id BIGINT UNSIGNED NOT NULL,
    step_number INT NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (quality_plan_id) REFERENCES quality_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE quality_activity_matrix (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quality_plan_id BIGINT UNSIGNED NOT NULL,
    work_package VARCHAR(255) NOT NULL,
    quality_standard TEXT NULL,
    prevention_activities TEXT NULL,
    control_activities TEXT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (quality_plan_id) REFERENCES quality_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE quality_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quality_plan_id BIGINT UNSIGNED NOT NULL,
    role_name VARCHAR(150) NOT NULL,
    role_objectives TEXT NULL,
    role_functions TEXT NULL,
    authority_level TEXT NULL,
    reports_to VARCHAR(150) NULL,
    supervises VARCHAR(150) NULL,
    knowledge_requirements TEXT NULL,
    skill_requirements TEXT NULL,
    experience_requirements TEXT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (quality_plan_id) REFERENCES quality_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE quality_organization_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quality_plan_id BIGINT UNSIGNED NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    parent_name VARCHAR(150) NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (quality_plan_id) REFERENCES quality_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE quality_normative_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quality_plan_id BIGINT UNSIGNED NOT NULL,
    document_type ENUM('procedure','template','format','checklist','other') NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (quality_plan_id) REFERENCES quality_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_quality_plans_status_deleted ON quality_plans(status, deleted_at);
CREATE INDEX idx_users_status_role ON users(status, role);
CREATE INDEX idx_login_attempts_guard ON login_attempts(email, ip_address, success, created_at);
CREATE INDEX idx_versions_plan ON quality_plan_versions(quality_plan_id);
CREATE INDEX idx_baselines_plan ON quality_baselines(quality_plan_id);
CREATE INDEX idx_steps_plan ON process_improvement_steps(quality_plan_id);
CREATE INDEX idx_activity_plan ON quality_activity_matrix(quality_plan_id);
CREATE INDEX idx_roles_plan ON quality_roles(quality_plan_id);
CREATE INDEX idx_org_plan ON quality_organization_items(quality_plan_id);
CREATE INDEX idx_docs_plan_type ON quality_normative_documents(quality_plan_id, document_type);
