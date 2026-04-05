<div class="modal-backdrop" data-item-modal hidden>
    <div class="item-modal">
        <button class="icon-btn modal-close" type="button" data-close-item-modal aria-label="Close">
            <?= \App\Core\Ui::icon('close') ?>
        </button>
        <div class="item-modal-preview" data-item-preview></div>
        <div class="item-modal-body">
            <div class="item-modal-top">
                <div>
                    <h3 data-item-title></h3>
                    <p data-item-meta></p>
                </div>
                <span class="status-badge" data-item-status></span>
            </div>
            <div class="detail-stats">
                <div><span>Platform</span><strong data-item-platform></strong></div>
                <div><span>Post Type</span><strong data-item-type></strong></div>
                <div><span>Scheduled</span><strong data-item-date></strong></div>
                <div><span>Client</span><strong data-item-client></strong></div>
            </div>
            <div class="detail-section">
                <h4>Caption</h4>
                <p data-item-caption></p>
            </div>
            <div class="page-actions">
                <a class="btn btn-primary" href="#" data-item-details-link>Open Details Page</a>
            </div>
        </div>
    </div>
</div>
