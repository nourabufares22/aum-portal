<?php
// Shared form fields for Add and Edit job modals.
// When included for the Edit modal, field IDs are prefixed with "edit_".
// $jobFormDefaults is optionally set by the parent to pre-fill values.
$isEdit = isset($jobFormDefaults); // false = Add modal, true = Edit modal
$pfx    = $isEdit ? 'edit_' : '';  // ID prefix
$v      = function(string $key) use ($isEdit) {
    return ''; // JS fills edit fields; add fields start empty
};
?>
<div class="row g-3">

    <div class="col-md-8">
        <label class="form-label fw-medium">Job Title <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="title" id="<?= $pfx ?>title"
               placeholder="e.g. Assistant Professor — Computer Science" required
               value="<?= $isEdit ? '' : e($_POST['title'] ?? '') ?>">
    </div>

    <div class="col-md-4">
        <label class="form-label fw-medium">Status</label>
        <select class="form-select" name="status" id="<?= $pfx ?>status">
            <option value="active">Active</option>
            <option value="closed">Closed</option>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-medium">Department</label>
        <input type="text" class="form-control" name="department" id="<?= $pfx ?>department"
               placeholder="e.g. Computer Science"
               value="<?= $isEdit ? '' : e($_POST['department'] ?? '') ?>">
    </div>

    <div class="col-md-6">
        <label class="form-label fw-medium">Application Deadline</label>
        <input type="date" class="form-control" name="deadline" id="<?= $pfx ?>deadline"
               min="<?= date('Y-m-d') ?>"
               value="<?= $isEdit ? '' : e($_POST['deadline'] ?? '') ?>">
        <div class="form-text">Leave blank for open deadline.</div>
    </div>

    <div class="col-12">
        <label class="form-label fw-medium">Job Description</label>
        <textarea class="form-control" name="description" id="<?= $pfx ?>description"
                  rows="5" placeholder="Describe the role, responsibilities, and what the candidate will be doing..."><?= $isEdit ? '' : e($_POST['description'] ?? '') ?></textarea>
    </div>

    <div class="col-12">
        <label class="form-label fw-medium">Requirements</label>
        <textarea class="form-control" name="requirements" id="<?= $pfx ?>requirements"
                  rows="4" placeholder="List each requirement on a new line, e.g.&#10;PhD in related field&#10;Minimum 2 years experience"><?= $isEdit ? '' : e($_POST['requirements'] ?? '') ?></textarea>
        <div class="form-text">Enter each requirement on a separate line.</div>
    </div>

</div>
