<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/ActivityLog.php';
requireLogin();

$uid      = $_SESSION['user_id'];
$userModel= new User();
$user     = $userModel->findById($uid);
$contrib  = $userModel->getContributionStats($uid);
$activity = (new ActivityLog())->getUserActivity($uid, 15);

// Avatar helper
$avatarUrl = !empty($user['avatar'])
    ? BASE_URL . 'assets/uploads/avatars/' . sanitize($user['avatar'])
    : null;

// Passport helper
$passportFile = $user['passport_document'] ?? null;
$passportUrl  = $passportFile
    ? BASE_URL . 'assets/uploads/passports/' . sanitize($passportFile)
    : null;
$passportExt  = $passportFile ? strtolower(pathinfo($passportFile, PATHINFO_EXTENSION)) : null;

$pageTitle = 'My Profile';
$navFile   = isAdmin() && !empty($_SESSION['admin_secret_access']) ? __DIR__ . '/layouts/navbar_admin.php' : __DIR__ . '/layouts/navbar_researcher.php';
include __DIR__ . '/layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include $navFile; ?>
<main class="flex-grow-1 py-4">
<div class="container px-4" style="max-width:960px">

    <?= renderFlash() ?>

    <h1 class="h3 fw-bold mb-4"><i class="bi bi-person-circle"></i> My Profile</h1>

    <div class="row g-4">

        <!-- ── LEFT COLUMN ──────────────────────────────────── -->
        <div class="col-md-4">

            <!-- Photo Card -->
            <div class="card border-0 shadow-sm mb-4 text-center">
                <div class="card-body p-4">

                    <!-- Avatar display -->
                    <div class="position-relative d-inline-block mb-3">
                        <?php if ($avatarUrl): ?>
                            <img src="<?= $avatarUrl ?>" alt="Profile Photo"
                                 class="rounded-circle border border-3 border-success shadow"
                                 style="width:120px;height:120px;object-fit:cover"
                                 id="avatarPreview">
                        <?php else: ?>
                            <div class="rounded-circle border border-3 border-secondary d-flex align-items-center
                                        justify-content-center bg-light shadow"
                                 style="width:120px;height:120px;margin:0 auto"
                                 id="avatarPlaceholder">
                                <i class="bi bi-person-fill text-secondary" style="font-size:3.5rem"></i>
                            </div>
                            <img src="" alt="" class="rounded-circle border border-3 border-success shadow d-none"
                                 style="width:120px;height:120px;object-fit:cover"
                                 id="avatarPreview">
                        <?php endif; ?>

                        <!-- Camera overlay button -->
                        <label for="avatarInput"
                               class="position-absolute bottom-0 end-0 btn btn-success btn-sm rounded-circle p-1"
                               style="width:32px;height:32px;cursor:pointer"
                               title="Change photo">
                            <i class="bi bi-camera-fill" style="font-size:.85rem"></i>
                        </label>
                    </div>

                    <h5 class="fw-bold mb-0"><?= sanitize($user['name']) ?></h5>
                    <span class="badge <?= $user['role']==='admin'?'bg-danger':'bg-success' ?> mt-1">
                        <?= ucfirst($user['role']) ?>
                    </span>
                    <?php if ($user['institution']): ?>
                    <p class="text-muted small mt-2 mb-0">
                        <i class="bi bi-building me-1"></i><?= sanitize($user['institution']) ?>
                    </p>
                    <?php endif; ?>

                    <!-- Upload form (triggered by camera button) -->
                    <form method="POST" action="<?= BASE_URL ?>controllers/process.php"
                          enctype="multipart/form-data" id="avatarForm" class="mt-3">
                        <input type="hidden" name="action"     value="upload_avatar">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="file" name="avatar" id="avatarInput"
                               accept="image/jpeg,image/png,image/gif,image/webp"
                               class="d-none">

                        <div id="avatarActions" class="d-none mt-2">
                            <button type="submit" class="btn btn-success btn-sm px-3">
                                <i class="bi bi-upload me-1"></i>Upload Photo
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm ms-1"
                                    onclick="cancelAvatarPreview()">
                                Cancel
                            </button>
                        </div>
                    </form>

                    <!-- Remove photo -->
                    <?php if ($avatarUrl): ?>
                    <form method="POST" action="<?= BASE_URL ?>controllers/process.php"
                          class="mt-2"
                          onsubmit="return confirm('Remove your profile photo?')">
                        <input type="hidden" name="action"     value="remove_avatar">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash me-1"></i>Remove Photo
                        </button>
                    </form>
                    <?php endif; ?>

                    <p class="text-muted mt-3 mb-0" style="font-size:.75rem">
                        JPG, PNG, GIF or WEBP · Max 2MB
                    </p>
                </div>
            </div>

            <!-- Account Info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">Account Info</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Member since</dt>
                        <dd class="col-7"><?= formatDate($user['created_at']) ?></dd>
                        <dt class="col-5 text-muted">Last updated</dt>
                        <dd class="col-7"><?= formatDate($user['updated_at']) ?></dd>
                    </dl>
                </div>
            </div>

            <!-- Contribution Stats -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">My Contributions</div>
                <div class="card-body">
                    <?php
                    $ins = $contrib['insights'];
                    $rec = $contrib['recommendations'];
                    ?>
                    <div class="row g-2 text-center">
                        <div class="col-6">
                            <div class="p-2 bg-light rounded">
                                <div class="fw-bold fs-5"><?= (int)$ins['total'] ?></div>
                                <small class="text-muted">Insights</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 bg-light rounded">
                                <div class="fw-bold fs-5"><?= (int)$rec['total'] ?></div>
                                <small class="text-muted">Recommendations</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 bg-success bg-opacity-10 rounded">
                                <div class="fw-bold text-success"><?= (int)$ins['approved'] + (int)$rec['approved'] ?></div>
                                <small class="text-muted">Approved</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 bg-warning bg-opacity-10 rounded">
                                <div class="fw-bold text-warning"><?= (int)$ins['pending'] + (int)$rec['pending'] ?></div>
                                <small class="text-muted">Pending</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 bg-danger bg-opacity-10 rounded">
                                <div class="fw-bold text-danger"><?= (int)$ins['rejected'] + (int)$rec['rejected'] ?></div>
                                <small class="text-muted">Rejected</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── RIGHT COLUMN ─────────────────────────────────── -->
        <div class="col-md-8">

            <!-- Profile Info Form -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-person-lines-fill text-success me-2"></i>Profile Information
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="<?= BASE_URL ?>controllers/process.php" novalidate>
                        <input type="hidden" name="action"     value="update_profile">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required
                                       value="<?= sanitize($user['name']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required
                                       value="<?= sanitize($user['email']) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Institution</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-building"></i></span>
                                    <input type="text" name="institution" class="form-control"
                                           placeholder="University / Research Institute"
                                           value="<?= sanitize($user['institution'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Bio</label>
                                <textarea name="bio" class="form-control" rows="3"
                                          placeholder="Brief description of your research..."><?= sanitize($user['bio'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success px-4">
                                <i class="bi bi-save me-1"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Passport Document -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-file-earmark-person text-primary me-2"></i>Passport / ID Document
                </div>
                <div class="card-body p-4">

                    <?php if ($passportUrl): ?>
                    <!-- Current document preview -->
                    <div class="d-flex align-items-center gap-3 mb-3 p-3 bg-light rounded">
                        <?php if ($passportExt === 'pdf'): ?>
                            <i class="bi bi-file-earmark-pdf text-danger" style="font-size:2.5rem"></i>
                        <?php else: ?>
                            <img src="<?= $passportUrl ?>" alt="Passport"
                                 class="rounded border" style="height:64px;max-width:120px;object-fit:cover">
                        <?php endif; ?>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="fw-semibold small text-truncate"><?= sanitize($passportFile) ?></div>
                            <div class="text-muted" style="font-size:.75rem">
                                <?= strtoupper($passportExt) ?> &middot; uploaded
                            </div>
                        </div>
                        <a href="<?= $passportUrl ?>" target="_blank" rel="noopener"
                           class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-eye me-1"></i>View
                        </a>
                    </div>
                    <?php else: ?>
                    <p class="text-muted small mb-3">No passport document uploaded yet.</p>
                    <?php endif; ?>

                    <!-- Upload form -->
                    <form method="POST" action="<?= BASE_URL ?>controllers/process.php"
                          enctype="multipart/form-data" id="passportForm">
                        <input type="hidden" name="action"     value="upload_passport">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                        <div class="mb-3">
                            <label for="passportInput" class="form-label fw-semibold">
                                <?= $passportUrl ? 'Replace Document' : 'Upload Document' ?>
                            </label>
                            <input type="file" name="passport" id="passportInput"
                                   accept=".pdf,.jpg,.jpeg,.png"
                                   class="form-control"
                                   onchange="previewPassport(this)">
                            <div class="form-text">PDF, JPG, or PNG &middot; Max 5MB</div>
                        </div>

                        <!-- Client-side preview (images only) -->
                        <div id="passportPreviewWrap" class="mb-3 d-none">
                            <img id="passportPreviewImg" src="" alt="Preview"
                                 class="rounded border" style="max-height:120px;max-width:100%">
                        </div>

                        <button type="submit" class="btn btn-primary px-4" id="passportSubmitBtn" disabled>
                            <i class="bi bi-upload me-1"></i>Upload Document
                        </button>
                    </form>

                    <!-- Remove form -->
                    <?php if ($passportUrl): ?>
                    <form method="POST" action="<?= BASE_URL ?>controllers/process.php"
                          class="mt-3"
                          onsubmit="return confirm('Remove your passport document?')">
                        <input type="hidden" name="action"     value="remove_passport">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash me-1"></i>Remove Document
                        </button>
                    </form>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Change Password -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-lock text-warning me-2"></i>Change Password
                </div>
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Current Password</label>
                                <div class="input-group">
                                    <input type="password" name="current_password" id="curPass"
                                           class="form-control" required placeholder="Current">
                                    <button type="button" class="btn btn-outline-secondary toggle-password"
                                            data-target="curPass"><i class="bi bi-eye"></i></button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">New Password</label>
                                <div class="input-group">
                                    <input type="password" name="new_password" id="newPass"
                                           class="form-control" required minlength="8" placeholder="New">
                                    <button type="button" class="btn btn-outline-secondary toggle-password"
                                            data-target="newPass"><i class="bi bi-eye"></i></button>
                                </div>
                                <div class="form-text">Min 8 chars, 1 uppercase, 1 number.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Confirm Password</label>
                                <input type="password" name="confirm_password" id="confPass"
                                       class="form-control" required placeholder="Confirm">
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-warning px-4">
                                <i class="bi bi-lock me-1"></i>Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-clock-history text-secondary me-2"></i>Recent Activity
                </div>
                <ul class="list-group list-group-flush" style="max-height:300px;overflow-y:auto">
                    <?php if (empty($activity)): ?>
                    <li class="list-group-item text-muted small text-center py-4">No activity yet.</li>
                    <?php else: foreach ($activity as $a): ?>
                    <li class="list-group-item py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-primary bg-opacity-75 small">
                                <?= sanitize(str_replace('_',' ', $a['action'])) ?>
                            </span>
                            <small class="text-muted"><?= date('M d, H:i', strtotime($a['created_at'])) ?></small>
                        </div>
                        <?php if ($a['details']): ?>
                        <div class="text-muted small mt-1"><?= sanitize(mb_strimwidth($a['details'],0,70,'…')) ?></div>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>

        </div>
    </div>

</div>
</main>
<?php include __DIR__ . '/layouts/footer.php'; ?>
</div>

<script>
const avatarInput     = document.getElementById('avatarInput');
const avatarPreview   = document.getElementById('avatarPreview');
const avatarPlaceholder = document.getElementById('avatarPlaceholder');
const avatarActions   = document.getElementById('avatarActions');

avatarInput.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;

    // Client-side size check
    if (file.size > 2 * 1024 * 1024) {
        alert('File is too large. Maximum size is 2MB.');
        this.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
        avatarPreview.src = e.target.result;
        avatarPreview.classList.remove('d-none');
        if (avatarPlaceholder) avatarPlaceholder.classList.add('d-none');
        avatarActions.classList.remove('d-none');
    };
    reader.readAsDataURL(file);
});

function cancelAvatarPreview() {
    avatarInput.value = '';
    avatarActions.classList.add('d-none');
    <?php if (!$avatarUrl): ?>
    avatarPreview.classList.add('d-none');
    avatarPreview.src = '';
    if (avatarPlaceholder) avatarPlaceholder.classList.remove('d-none');
    <?php else: ?>
    avatarPreview.src = '<?= $avatarUrl ?>';
    <?php endif; ?>
}

// ── Passport preview ──────────────────────────────────────────────────────────
function previewPassport(input) {
    const btn     = document.getElementById('passportSubmitBtn');
    const wrap    = document.getElementById('passportPreviewWrap');
    const preview = document.getElementById('passportPreviewImg');
    const file    = input.files[0];

    btn.disabled = !file;
    wrap.classList.add('d-none');
    preview.src = '';

    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
        alert('File is too large. Maximum size is 5MB.');
        input.value = '';
        btn.disabled = true;
        return;
    }

    // Show image preview for non-PDF files
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            wrap.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    }
}
</script>
