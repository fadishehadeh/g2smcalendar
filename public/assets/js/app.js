document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('ui-ready');
    window.requestAnimationFrame(() => {
        document.body.classList.add('is-loaded');
    });

    const readJsonStore = (selector) => {
        const node = document.querySelector(selector);
        if (!node) {
            return {};
        }

        try {
            return JSON.parse(node.textContent || '{}');
        } catch (error) {
            return {};
        }
    };

    const itemStores = {
        calendar: readJsonStore('[data-item-store="calendar"]'),
        quick: readJsonStore('[data-item-store="quick"]'),
    };

    const sidebar = document.querySelector('[data-sidebar]');
    const sidebarToggles = [...document.querySelectorAll('[data-sidebar-toggle]')];

    if (sidebar && sidebarToggles.length) {
        const desktopQuery = window.matchMedia('(max-width: 980px)');
        const syncSidebarToggleLabels = () => {
            const isCollapsed = document.body.classList.contains('sidebar-collapsed');
            const isMobileOpen = sidebar.classList.contains('is-open');

            sidebarToggles.forEach((toggle) => {
                toggle.setAttribute(
                    'aria-label',
                    desktopQuery.matches
                        ? (isMobileOpen ? 'Hide sidebar' : 'Show sidebar')
                        : (isCollapsed ? 'Show sidebar' : 'Hide sidebar')
                );
            });
        };

        try {
            if (window.localStorage.getItem('g2.sidebar.collapsed') === '1' && !desktopQuery.matches) {
                document.body.classList.add('sidebar-collapsed');
            }
        } catch (error) {
            // Ignore storage access issues.
        }

        sidebarToggles.forEach((toggle) => {
            toggle.addEventListener('click', () => {
                if (desktopQuery.matches) {
                    sidebar.classList.toggle('is-open');
                } else {
                    document.body.classList.toggle('sidebar-collapsed');
                    try {
                        window.localStorage.setItem(
                            'g2.sidebar.collapsed',
                            document.body.classList.contains('sidebar-collapsed') ? '1' : '0'
                        );
                    } catch (error) {
                        // Ignore storage access issues.
                    }
                }

                syncSidebarToggleLabels();
            });
        });

        desktopQuery.addEventListener('change', () => {
            sidebar.classList.remove('is-open');
            syncSidebarToggleLabels();
        });

        syncSidebarToggleLabels();
    }

    const demoCards = document.querySelectorAll('[data-demo-email]');
    const loginEmail = document.getElementById('loginEmail');
    const loginPassword = document.getElementById('loginPassword');

    demoCards.forEach((card) => {
        card.addEventListener('click', () => {
            if (loginEmail) {
                loginEmail.value = card.getAttribute('data-demo-email') || '';
            }
            if (loginPassword) {
                loginPassword.value = card.getAttribute('data-demo-password') || '';
            }
        });
    });

    const modal = document.querySelector('[data-item-modal]');
    if (modal) {
        const title = modal.querySelector('[data-item-title]');
        const meta = modal.querySelector('[data-item-meta]');
        const status = modal.querySelector('[data-item-status]');
        const preview = modal.querySelector('[data-item-preview]');
        const platform = modal.querySelector('[data-item-platform]');
        const type = modal.querySelector('[data-item-type]');
        const date = modal.querySelector('[data-item-date]');
        const client = modal.querySelector('[data-item-client]');
        const caption = modal.querySelector('[data-item-caption]');
        const detailsLink = modal.querySelector('[data-item-details-link]');

        const openItemModal = (item) => {
            if (!item || !item.id) {
                modal.hidden = true;
                document.body.classList.remove('modal-open');
                return;
            }

            title.textContent = item.title || '';
            meta.textContent = `${item.client || ''} - ${item.platform || ''}`;
            status.textContent = item.status || '';
            status.className = `status-badge ${item.statusClass || ''}`;
            platform.textContent = item.platform || '';
            type.textContent = item.post_type || item.postType || '';
            date.textContent = item.date || '';
            client.textContent = item.client || '';
            caption.textContent = item.caption || '';
            detailsLink.href = item.detailsUrl || '#';

            if (item.preview) {
                preview.innerHTML = item.previewKind === 'video'
                    ? `<video src="${item.preview}" controls muted playsinline preload="metadata"></video>`
                    : `<img src="${item.preview}" alt="${item.title || ''}">`;
            } else {
                preview.innerHTML = '<div class="empty-state"><p>No artwork uploaded yet.</p></div>';
            }

            modal.hidden = false;
            document.body.classList.add('modal-open');
        };

        document.querySelectorAll('[data-item-id]').forEach((trigger) => {
            trigger.addEventListener('click', (event) => {
                const itemId = trigger.getAttribute('data-item-id') || '';
                const source = trigger.getAttribute('data-item-source') || 'calendar';
                const item = itemStores[source]?.[itemId] || null;

                if (!item || !item.id) {
                    const fallbackHref = trigger.getAttribute('href');
                    if (fallbackHref) {
                        window.location.href = fallbackHref;
                    }
                    return;
                }

                event.preventDefault();
                openItemModal(item);
            });
        });

        modal.hidden = true;

        const closeModal = () => {
            modal.hidden = true;
            document.body.classList.remove('modal-open');
        };

        modal.addEventListener('click', (event) => {
            if (event.target === modal || event.target.closest('[data-close-item-modal]')) {
                closeModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });
    }

    document.querySelectorAll('[data-row-link]').forEach((row) => {
        row.addEventListener('click', (event) => {
            if (event.target.closest('a, button, input, select, textarea, form')) {
                return;
            }

            const href = row.getAttribute('data-row-link');
            if (href) {
                window.location.href = href;
            }
        });
    });

    const bulkForm = document.querySelector('[data-post-bulk-form]');
    if (bulkForm) {
        const selectAll = bulkForm.querySelector('[data-select-all]');
        const rowChecks = [...bulkForm.querySelectorAll('[data-select-row]')];
        const selectionCount = bulkForm.querySelector('[data-selection-count]');
        const bulkActionButtons = [...bulkForm.querySelectorAll('[data-bulk-action]')];
        const editButton = bulkForm.querySelector('[data-bulk-edit]');

        const syncBulkState = () => {
            const selected = rowChecks.filter((input) => input.checked);
            const count = selected.length;

            if (selectionCount) {
                selectionCount.textContent = `${count} selected`;
            }

            if (selectAll) {
                selectAll.checked = count > 0 && count === rowChecks.length;
                selectAll.indeterminate = count > 0 && count < rowChecks.length;
            }
        };

        if (selectAll) {
            selectAll.addEventListener('change', () => {
                rowChecks.forEach((input) => {
                    input.checked = selectAll.checked;
                });
                syncBulkState();
            });
        }

        rowChecks.forEach((input) => {
            input.addEventListener('change', syncBulkState);
        });

        syncBulkState();
    }

    const validationForms = document.querySelectorAll('[data-inline-validate]');
    const ensureFeedbackNode = (field) => {
        const container = field.closest('label') || field.parentElement;
        if (!container) {
            return null;
        }

        let feedback = container.querySelector('[data-field-feedback]');
        if (!feedback) {
            feedback = document.createElement('small');
            feedback.className = 'field-feedback';
            feedback.setAttribute('data-field-feedback', '');
            container.appendChild(feedback);
        }

        return feedback;
    };

    const syncFieldState = (field, force = false) => {
        if (!field || field.disabled || field.type === 'hidden' || field.type === 'checkbox' || field.type === 'radio') {
            return true;
        }

        const feedback = ensureFeedbackNode(field);
        const value = String(field.value || '').trim();
        const shouldValidate = force || value !== '' || field.required;
        const isOptionalEmpty = !field.required && value === '';
        const isValid = isOptionalEmpty || !shouldValidate || field.checkValidity();

        field.classList.toggle('is-valid', shouldValidate && !isOptionalEmpty && isValid);
        field.classList.toggle('is-invalid', shouldValidate && !isValid);

        if (feedback) {
            if (shouldValidate && !isValid) {
                feedback.textContent = field.validationMessage || 'Check this field.';
                feedback.classList.add('is-visible', 'is-invalid');
                feedback.classList.remove('is-valid');
            } else if (!isOptionalEmpty && shouldValidate && isValid) {
                feedback.textContent = 'Looks good.';
                feedback.classList.add('is-visible', 'is-valid');
                feedback.classList.remove('is-invalid');
            } else {
                feedback.textContent = '';
                feedback.classList.remove('is-visible', 'is-invalid', 'is-valid');
            }
        }

        return isValid;
    };

    validationForms.forEach((form) => {
        const fields = [...form.querySelectorAll('input, select, textarea')];
        form.querySelectorAll('[data-requires-field]').forEach((toggle) => {
            const targetName = toggle.getAttribute('data-requires-field');
            const targetField = targetName ? form.querySelector(`[name="${targetName}"]`) : null;
            if (!targetField) {
                return;
            }

            const syncDependency = () => {
                targetField.required = !!toggle.checked;
                syncFieldState(targetField, !!toggle.checked && document.activeElement !== targetField);
            };

            toggle.addEventListener('change', syncDependency);
            syncDependency();
        });

        fields.forEach((field) => {
            field.addEventListener('input', () => syncFieldState(field));
            field.addEventListener('change', () => syncFieldState(field, field.tagName === 'SELECT'));
            field.addEventListener('blur', () => syncFieldState(field, true));
        });

        form.addEventListener('submit', (event) => {
            const invalidField = fields.find((field) => !syncFieldState(field, true));
            if (invalidField) {
                event.preventDefault();
                invalidField.focus();
            }
        });
    });

    document.querySelectorAll('form[data-loading-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const exactName = form.getAttribute('data-confirm-exact-name');
            if (exactName) {
                const entity = form.getAttribute('data-confirm-entity') || 'record';
                const typed = window.prompt(`Type the ${entity} name exactly to confirm deletion:\n${exactName}`, '');
                if (typed !== exactName) {
                    event.preventDefault();
                    return;
                }
            }

            const submitter = event.submitter;
            if (!submitter || submitter.disabled) {
                return;
            }

            if (!form.checkValidity()) {
                return;
            }

            const label = submitter.getAttribute('data-loading-text');
            if (label) {
                submitter.dataset.originalText = submitter.textContent || '';
                submitter.textContent = label;
            }

            submitter.disabled = true;
            form.classList.add('is-submitting');
        });
    });

    const clientReviewForm = document.querySelector('[data-client-review-form], [data-client-guided-review]');
    const clientReviewComment = document.querySelector('[data-client-review-comment]');
    if (clientReviewForm && clientReviewComment) {
        clientReviewForm.addEventListener('submit', (event) => {
            const submitter = event.submitter;
            const action = submitter?.value || submitter?.getAttribute('value') || '';
            const selectedAction = clientReviewForm.querySelector('input[name="review_action"]:checked')?.value || '';
            const isRejected = action === 'Rejected' || selectedAction === 'request_changes';
            clientReviewComment.required = isRejected;

            if (isRejected && !clientReviewComment.value.trim()) {
                event.preventDefault();
                clientReviewComment.focus();
                alert('Requesting changes requires a short note.');
            }
        });
    }

    const artworkModal = document.querySelector('[data-artwork-modal]');
    const artworkModalTrigger = document.querySelector('[data-artwork-modal-open]');
    if (artworkModal && artworkModalTrigger) {
        const closeArtworkModal = () => {
            artworkModal.hidden = true;
            document.body.classList.remove('modal-open');
        };

        artworkModalTrigger.addEventListener('click', () => {
            artworkModal.hidden = false;
            document.body.classList.add('modal-open');
        });

        artworkModal.addEventListener('click', (event) => {
            if (event.target === artworkModal || event.target.closest('[data-close-artwork-modal]')) {
                closeArtworkModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !artworkModal.hidden) {
                closeArtworkModal();
            }
        });
    }

    const filterPanel = document.querySelector('[data-collapsible-filter]');
    if (filterPanel) {
        const filterBody = filterPanel.querySelector('[data-filter-body]');
        const filterToggle = filterPanel.querySelector('[data-filter-toggle]');
        const filterToggleLabel = filterPanel.querySelector('[data-filter-toggle-label]');

        if (filterBody && filterToggle && filterToggleLabel) {
            const syncFilterPanel = () => {
                const isOpen = filterBody.classList.contains('is-open');
                filterToggleLabel.textContent = isOpen ? 'Hide Filters' : 'Show Filters';
            };

            filterToggle.addEventListener('click', () => {
                filterBody.classList.toggle('is-open');
                syncFilterPanel();
            });

            syncFilterPanel();
        }
    }

    const revealItems = document.querySelectorAll('.kpi-card, .card, .toolbar-card, .approval-card, .entity-card, .assignment-card, .table-card, .calendar-card, .wizard-step, .wizard-date-card, .wizard-review-card');
    if ('IntersectionObserver' in window && revealItems.length) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        revealItems.forEach((item, index) => {
            item.style.setProperty('--reveal-delay', `${Math.min(index * 35, 280)}ms`);
            item.classList.add('reveal');
            observer.observe(item);
        });
    }

    const wizard = document.querySelector('[data-wizard]');
    if (wizard) {
        const draftNode = document.querySelector('[data-wizard-draft="bulk-posts"]');
        let bulkDraft = {};
        try {
            bulkDraft = draftNode ? JSON.parse(draftNode.textContent || '{}') : {};
        } catch (error) {
            bulkDraft = {};
        }
        const form = wizard.querySelector('[data-wizard-form]');
        const monthInput = wizard.querySelector('[data-wizard-month]');
        const yearInput = wizard.querySelector('[data-wizard-year]');
        const monthLabel = wizard.querySelector('[data-wizard-month-label]');
        const calendar = wizard.querySelector('[data-wizard-calendar]');
        const datePills = wizard.querySelector('[data-selected-date-pills]');
        const dateCards = wizard.querySelector('[data-wizard-date-cards]');
        const selectedDatesInput = wizard.querySelector('[data-selected-dates]');
        const review = wizard.querySelector('[data-wizard-review]');
        const backBtn = wizard.querySelector('[data-step-back]');
        const nextBtn = wizard.querySelector('[data-step-next]');
        const submitBtn = wizard.querySelector('[data-step-submit]');
        const artworkTypeInput = wizard.querySelector('[data-artwork-type]');
        const artworkSizeInput = wizard.querySelector('[data-artwork-size]');
        const panels = [...wizard.querySelectorAll('[data-step-panel]')];
        const steps = [...wizard.querySelectorAll('[data-step-nav]')];

        let activeStep = 1;
        let selectedDates = [];
        const channelData = {};

        const platformOptions = ['Instagram', 'Facebook', 'TikTok', 'YouTube', 'X'];
        const postTypeOptions = {
            Instagram: ['Post', 'Story', 'Reel', 'Carousel'],
            Facebook: ['Post', 'Story', 'Reel', 'Carousel', 'Video'],
            TikTok: ['Video', 'Story', 'Carousel'],
            YouTube: ['Short', 'Video', 'Thumbnail'],
            X: ['Post', 'Thread', 'Video'],
        };
        const sizePresets = {
            Instagram: {
                Image: {
                    Post: [
                        { value: '1080x1080', label: 'Feed Square 1080x1080 (1:1)' },
                        { value: '1080x1350', label: 'Feed Portrait 1080x1350 (4:5)' },
                    ],
                    Story: [{ value: '1080x1920', label: 'Story 1080x1920 (9:16)' }],
                    Reel: [{ value: '1080x1920', label: 'Reel Cover 1080x1920 (9:16)' }],
                    Carousel: [
                        { value: '1080x1080', label: 'Carousel Square 1080x1080 (1:1)' },
                        { value: '1080x1350', label: 'Carousel Portrait 1080x1350 (4:5)' },
                    ],
                },
                Video: {
                    Post: [
                        { value: '1080x1350', label: 'Feed Video 1080x1350 (4:5)' },
                        { value: '1080x1080', label: 'Feed Video 1080x1080 (1:1)' },
                    ],
                    Story: [{ value: '1080x1920', label: 'Story Video 1080x1920 (9:16)' }],
                    Reel: [{ value: '1080x1920', label: 'Reel 1080x1920 (9:16)' }],
                    Carousel: [{ value: '1080x1080', label: 'Carousel Video 1080x1080 (1:1)' }],
                },
            },
            Facebook: {
                Image: {
                    Post: [
                        { value: '1080x1080', label: 'Feed Image 1080x1080 (1:1)' },
                        { value: '1080x1350', label: 'Feed Portrait 1080x1350 (4:5)' },
                    ],
                    Story: [{ value: '1080x1920', label: 'Story 1080x1920 (9:16)' }],
                    Reel: [{ value: '1080x1920', label: 'Reel Cover 1080x1920 (9:16)' }],
                    Carousel: [{ value: '1080x1080', label: 'Carousel 1080x1080 (1:1)' }],
                    Video: [{ value: '1920x1080', label: 'Landscape Video 1920x1080 (16:9)' }],
                },
                Video: {
                    Post: [{ value: '1080x1080', label: 'Feed Video 1080x1080 (1:1)' }],
                    Story: [{ value: '1080x1920', label: 'Story Video 1080x1920 (9:16)' }],
                    Reel: [{ value: '1080x1920', label: 'Reel 1080x1920 (9:16)' }],
                    Carousel: [{ value: '1080x1080', label: 'Carousel Video 1080x1080 (1:1)' }],
                    Video: [{ value: '1920x1080', label: 'Landscape Video 1920x1080 (16:9)' }],
                },
            },
            TikTok: {
                Image: {
                    Video: [{ value: '1080x1920', label: 'Vertical Visual 1080x1920 (9:16)' }],
                    Story: [{ value: '1080x1920', label: 'Story Visual 1080x1920 (9:16)' }],
                    Carousel: [{ value: '1080x1920', label: 'Carousel Visual 1080x1920 (9:16)' }],
                },
                Video: {
                    Video: [{ value: '1080x1920', label: 'TikTok Video 1080x1920 (9:16)' }],
                    Story: [{ value: '1080x1920', label: 'TikTok Story 1080x1920 (9:16)' }],
                    Carousel: [{ value: '1080x1920', label: 'Carousel Motion 1080x1920 (9:16)' }],
                },
            },
            YouTube: {
                Image: {
                    Short: [{ value: '1080x1920', label: 'Short Cover 1080x1920 (9:16)' }],
                    Video: [{ value: '1280x720', label: 'Thumbnail 1280x720 (16:9)' }],
                    Thumbnail: [{ value: '1280x720', label: 'Thumbnail 1280x720 (16:9)' }],
                },
                Video: {
                    Short: [{ value: '1080x1920', label: 'Short Video 1080x1920 (9:16)' }],
                    Video: [{ value: '1920x1080', label: 'Long-form Video 1920x1080 (16:9)' }],
                    Thumbnail: [{ value: '1280x720', label: 'Thumbnail 1280x720 (16:9)' }],
                },
            },
            X: {
                Image: {
                    Post: [
                        { value: '1600x900', label: 'Landscape 1600x900 (16:9)' },
                        { value: '1080x1350', label: 'Portrait 1080x1350 (4:5)' },
                    ],
                    Thread: [{ value: '1600x900', label: 'Thread Visual 1600x900 (16:9)' }],
                    Video: [{ value: '1600x900', label: 'Video Thumbnail 1600x900 (16:9)' }],
                },
                Video: {
                    Post: [{ value: '1600x900', label: 'Landscape Video 1600x900 (16:9)' }],
                    Thread: [{ value: '1600x900', label: 'Thread Video 1600x900 (16:9)' }],
                    Video: [{ value: '1600x900', label: 'X Video 1600x900 (16:9)' }],
                },
            },
        };
        const fallbackSizes = [
            { value: '1080x1080', label: 'Square 1080x1080 (1:1)' },
            { value: '1080x1350', label: 'Portrait 1080x1350 (4:5)' },
            { value: '1080x1920', label: 'Vertical 1080x1920 (9:16)' },
            { value: '1920x1080', label: 'Landscape 1920x1080 (16:9)' },
        ];

        const monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        const syncSelectedDates = () => {
            selectedDates.sort((a, b) => a - b);
            selectedDatesInput.value = selectedDates.join(',');
            datePills.innerHTML = selectedDates
                .map((day) => `<span class="pill">${day} ${monthNames[Number(monthInput.value) - 1]}</span>`)
                .join('');
        };

        const ensureChannelRows = (day) => {
            if (!channelData[day] || channelData[day].length === 0) {
                channelData[day] = [{ platform: 'Instagram', postType: 'Post', quantity: 1 }];
            }
        };

        const optionsForPostType = (platform) => postTypeOptions[platform] || ['Post'];

        const updateArtworkSizeOptions = () => {
            const artworkType = artworkTypeInput?.value || 'Image';
            const presetMap = new Map();

            selectedDates.forEach((day) => {
                ensureChannelRows(day);
                channelData[day].forEach((row) => {
                    const presets = sizePresets[row.platform]?.[artworkType]?.[row.postType]
                        || sizePresets[row.platform]?.[artworkType]?.Post
                        || [];

                    presets.forEach((preset) => {
                        if (!presetMap.has(preset.value)) {
                            presetMap.set(preset.value, preset.label);
                        }
                    });
                });
            });

            const options = presetMap.size > 0
                ? [...presetMap.entries()].map(([value, label]) => ({ value, label }))
                : fallbackSizes;

            const previousValue = artworkSizeInput.value;
            artworkSizeInput.innerHTML = options
                .map((option) => `<option value="${option.value}">${option.label}</option>`)
                .join('');

            const hasPrevious = options.some((option) => option.value === previousValue);
            const draftValue = artworkSizeInput.getAttribute('data-default-value') || '';
            if (!hasPrevious && draftValue && options.some((option) => option.value === draftValue)) {
                artworkSizeInput.value = draftValue;
            } else {
                artworkSizeInput.value = hasPrevious ? previousValue : options[0].value;
            }
        };

        const syncHiddenChannelInputs = () => {
            form.querySelectorAll('[data-generated-input]').forEach((input) => input.remove());
            selectedDatesInput.value = selectedDates.join(',');

            selectedDates.forEach((day) => {
                ensureChannelRows(day);
                channelData[day].forEach((row) => {
                    [
                        [`platforms[${day}][]`, row.platform || 'Instagram'],
                        [`post_types[${day}][]`, row.postType || 'Post'],
                        [`quantities[${day}][]`, Math.max(1, Number(row.quantity || 1)).toString()],
                    ].forEach(([name, value]) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = name;
                        input.value = value;
                        input.setAttribute('data-generated-input', '1');
                        form.appendChild(input);
                    });
                });
            });
        };

        const renderDateCards = () => {
            dateCards.innerHTML = '';

            selectedDates.forEach((day) => {
                ensureChannelRows(day);
                const card = document.createElement('article');
                card.className = 'wizard-date-card';
                card.setAttribute('data-day-card', String(day));

                const rowsMarkup = channelData[day].map((row, index) => `
                    <div class="wizard-channel-row" data-row-index="${index}">
                        <select data-field="platform">
                            ${platformOptions.map((platform) => `<option value="${platform}" ${row.platform === platform ? 'selected' : ''}>${platform}</option>`).join('')}
                        </select>
                        <select data-field="postType">
                            ${optionsForPostType(row.platform).map((type) => `<option value="${type}" ${row.postType === type ? 'selected' : ''}>${type}</option>`).join('')}
                        </select>
                        <input type="number" min="1" value="${row.quantity}" data-field="quantity">
                        <button class="icon-btn" type="button" data-remove-channel="${day}" data-remove-index="${index}">&times;</button>
                    </div>
                `).join('');

                card.innerHTML = `
                    <div class="card-head">
                        <div>
                            <h3>${day} ${monthNames[Number(monthInput.value) - 1]}</h3>
                            <p>Select the required channels and quantities for this date.</p>
                        </div>
                        <button class="btn btn-secondary" type="button" data-add-channel="${day}">Add Channel</button>
                    </div>
                    <div class="wizard-channel-list">${rowsMarkup}</div>
                `;

                dateCards.appendChild(card);
            });

            dateCards.querySelectorAll('[data-add-channel]').forEach((button) => {
                button.addEventListener('click', () => {
                    const day = button.getAttribute('data-add-channel');
                    channelData[day].push({ platform: '', postType: '', quantity: 1 });
                    renderDateCards();
                });
            });

            dateCards.querySelectorAll('[data-remove-channel]').forEach((button) => {
                button.addEventListener('click', () => {
                    const day = button.getAttribute('data-remove-channel');
                    const index = Number(button.getAttribute('data-remove-index'));
                    channelData[day].splice(index, 1);
                    ensureChannelRows(day);
                    renderDateCards();
                });
            });

            dateCards.querySelectorAll('[data-day-card]').forEach((card) => {
                const day = card.getAttribute('data-day-card');
                card.querySelectorAll('.wizard-channel-row').forEach((row) => {
                    const index = Number(row.getAttribute('data-row-index'));
                    const platformInput = row.querySelector('[data-field="platform"]');
                    const typeInput = row.querySelector('[data-field="postType"]');
                    const quantityInput = row.querySelector('[data-field="quantity"]');

                    platformInput.addEventListener('change', () => {
                        channelData[day][index].platform = platformInput.value;
                        const postTypeSelect = row.querySelector('[data-field="postType"]');
                        const availableTypes = optionsForPostType(platformInput.value);
                        if (!availableTypes.includes(channelData[day][index].postType)) {
                            channelData[day][index].postType = availableTypes[0];
                        }
                        postTypeSelect.innerHTML = availableTypes
                            .map((type) => `<option value="${type}" ${channelData[day][index].postType === type ? 'selected' : ''}>${type}</option>`)
                            .join('');
                        updateArtworkSizeOptions();
                    });
                    typeInput.addEventListener('change', () => {
                        channelData[day][index].postType = typeInput.value;
                        updateArtworkSizeOptions();
                    });
                    quantityInput.addEventListener('input', () => {
                        channelData[day][index].quantity = Math.max(1, Number(quantityInput.value || 1));
                    });
                });
            });

            updateArtworkSizeOptions();
        };

        const renderReview = () => {
            review.innerHTML = selectedDates.map((day) => {
                ensureChannelRows(day);
                const rows = channelData[day].map((row) => `<li>${row.quantity} x ${row.platform || 'Channel'} ${row.postType || 'Post'}</li>`).join('');
                return `<article class="wizard-review-card"><h3>${day} ${monthNames[Number(monthInput.value) - 1]}</h3><ul>${rows}</ul></article>`;
            }).join('');
            const total = selectedDates.reduce((sum, day) => {
                ensureChannelRows(day);
                return sum + channelData[day].reduce((carry, row) => carry + Math.max(1, Number(row.quantity || 1)), 0);
            }, 0);
            const totalNode = wizard.querySelector('[data-generated-total]');
            if (totalNode) {
                totalNode.textContent = `${total} post${total === 1 ? '' : 's'}`;
            }
        };

        const renderCalendar = () => {
            const month = Number(monthInput.value);
            const year = Number(yearInput.value);
            const firstDate = new Date(year, month - 1, 1);
            const daysInMonth = new Date(year, month, 0).getDate();
            const firstDay = (firstDate.getDay() + 6) % 7;

            monthLabel.textContent = `${monthNames[month - 1]} ${year}`;
            selectedDates = selectedDates.filter((day) => day <= daysInMonth);
            syncSelectedDates();

            let html = '<div class="wizard-calendar-labels"><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div><div>Sun</div></div><div class="wizard-calendar-grid">';
            for (let i = 0; i < firstDay; i++) {
                html += '<div class="wizard-day-cell is-empty"></div>';
            }
            for (let day = 1; day <= daysInMonth; day++) {
                const selected = selectedDates.includes(day) ? 'is-selected' : '';
                html += `<button class="wizard-day-cell ${selected}" type="button" data-day="${day}">${day}</button>`;
            }
            html += '</div>';
            calendar.innerHTML = html;

            calendar.querySelectorAll('[data-day]').forEach((button) => {
                button.addEventListener('click', () => {
                    const day = Number(button.getAttribute('data-day'));
                    if (selectedDates.includes(day)) {
                        selectedDates = selectedDates.filter((value) => value !== day);
                        delete channelData[day];
                    } else {
                        selectedDates.push(day);
                        ensureChannelRows(day);
                    }
                    syncSelectedDates();
                    renderCalendar();
                    renderDateCards();
                    renderReview();
                    updateArtworkSizeOptions();
                });
            });
        };

        const validateStep = () => {
            if (activeStep === 1) {
                const clientSelect = form.querySelector('select[name="client_id"]');
                if (clientSelect && !clientSelect.value) {
                    alert('Choose a client before continuing.');
                    clientSelect.focus();
                    return false;
                }
            }
            if (activeStep === 2 && selectedDates.length === 0) {
                alert('Select at least one date.');
                return false;
            }

            if (activeStep >= 3) {
                const invalid = selectedDates.some((day) => !channelData[day] || channelData[day].some((row) => !row.platform || !row.postType));
                if (invalid) {
                    alert('Complete the channel rows for each selected date.');
                    return false;
                }
            }

            return true;
        };

        const updateStep = () => {
            panels.forEach((panel) => panel.classList.toggle('is-active', Number(panel.getAttribute('data-step-panel')) === activeStep));
            steps.forEach((step) => step.classList.toggle('is-active', Number(step.getAttribute('data-step-nav')) === activeStep));
            backBtn.hidden = activeStep === 1;
            nextBtn.hidden = activeStep === panels.length;
            submitBtn.hidden = activeStep !== panels.length;

            if (activeStep >= 3) {
                renderDateCards();
            }
            if (activeStep >= 5) {
                renderReview();
            }
        };

        wizard.querySelector('[data-wizard-prev]').addEventListener('click', () => {
            const month = Number(monthInput.value);
            const year = Number(yearInput.value);
            const prev = new Date(year, month - 2, 1);
            monthInput.value = prev.getMonth() + 1;
            yearInput.value = prev.getFullYear();
            renderCalendar();
        });

        wizard.querySelector('[data-wizard-next]').addEventListener('click', () => {
            const month = Number(monthInput.value);
            const year = Number(yearInput.value);
            const next = new Date(year, month, 1);
            monthInput.value = next.getMonth() + 1;
            yearInput.value = next.getFullYear();
            renderCalendar();
        });

        monthInput.addEventListener('input', renderCalendar);
        yearInput.addEventListener('input', renderCalendar);
        artworkTypeInput.addEventListener('change', updateArtworkSizeOptions);

        backBtn.addEventListener('click', () => {
            activeStep = Math.max(1, activeStep - 1);
            updateStep();
        });

        nextBtn.addEventListener('click', () => {
            if (!validateStep()) {
                return;
            }

            activeStep = Math.min(panels.length, activeStep + 1);
            updateStep();
        });

        steps.forEach((step) => {
            step.addEventListener('click', () => {
                const target = Number(step.getAttribute('data-step-nav'));
                if (target <= activeStep || validateStep()) {
                    activeStep = target;
                    updateStep();
                }
            });
        });

        form.addEventListener('submit', (event) => {
            if (selectedDates.length === 0) {
                event.preventDefault();
                alert('Select at least one date.');
                return;
            }

            const invalid = selectedDates.some((day) => !channelData[day] || channelData[day].some((row) => !row.platform || !row.postType));
            if (invalid) {
                event.preventDefault();
                alert('Complete the channel rows for each selected date before generating items.');
                return;
            }

            syncHiddenChannelInputs();
        });

        if (bulkDraft.selected_dates) {
            selectedDates = String(bulkDraft.selected_dates)
                .split(',')
                .map((value) => Number(value))
                .filter((value) => value > 0);
        }
        Object.keys(bulkDraft.platforms || {}).forEach((day) => {
            const rows = [];
            const platforms = bulkDraft.platforms?.[day] || [];
            const postTypes = bulkDraft.post_types?.[day] || [];
            const quantities = bulkDraft.quantities?.[day] || [];
            platforms.forEach((platform, index) => {
                rows.push({
                    platform: platform || 'Instagram',
                    postType: postTypes[index] || 'Post',
                    quantity: Math.max(1, Number(quantities[index] || 1)),
                });
            });
            if (rows.length) {
                channelData[day] = rows;
            }
        });

        renderCalendar();
        syncSelectedDates();
        updateArtworkSizeOptions();
        updateStep();
    }

    const counters = document.querySelectorAll('.kpi-card strong');
    if (counters.length && 'IntersectionObserver' in window) {
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting || entry.target.dataset.counted === '1') {
                    return;
                }

                const node = entry.target;
                const raw = (node.textContent || '').replace(/,/g, '').trim();
                const target = Number(raw);
                if (!Number.isFinite(target)) {
                    node.dataset.counted = '1';
                    counterObserver.unobserve(node);
                    return;
                }

                const duration = 720;
                const start = performance.now();
                const step = (now) => {
                    const progress = Math.min(1, (now - start) / duration);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    node.textContent = Math.round(target * eased).toLocaleString();
                    if (progress < 1) {
                        requestAnimationFrame(step);
                    } else {
                        node.dataset.counted = '1';
                    }
                };

                requestAnimationFrame(step);
                counterObserver.unobserve(node);
            });
        }, { threshold: 0.35 });

        counters.forEach((node) => counterObserver.observe(node));
    }

    document.querySelectorAll('.flash').forEach((flash, index) => {
        flash.style.setProperty('--flash-delay', `${index * 90}ms`);
        const dismiss = () => {
            flash.classList.add('is-exit');
            window.setTimeout(() => flash.remove(), 260);
        };

        window.setTimeout(dismiss, 4600 + (index * 500));
        flash.addEventListener('click', dismiss);
    });

    document.querySelectorAll('[data-flow-wizard]').forEach((flowWizard) => {
        const panels = [...flowWizard.querySelectorAll('[data-step-panel]')];
        const steps = [...document.querySelectorAll('[data-step-nav]')].filter((node) => flowWizard.contains(node) || node.parentElement?.nextElementSibling === flowWizard);
        const backBtn = flowWizard.querySelector('[data-step-back]');
        const nextBtn = flowWizard.querySelector('[data-step-next]');
        const submitBtn = flowWizard.querySelector('[data-step-submit]');
        const summaryRoot = flowWizard.querySelector('[data-summary-root]');
        const form = flowWizard.querySelector('[data-wizard-form]');
        let activeStep = 1;

        const collectSummary = () => {
            if (!summaryRoot || !form) {
                return;
            }

            const summary = [];
            [...form.querySelectorAll('input, select, textarea')].forEach((field) => {
                const name = field.name || '';
                if (!name || name === '_csrf' || name === 'intent' || name === 'logo' || field.type === 'hidden') {
                    return;
                }
                if ((field.type === 'checkbox' || field.type === 'radio') && !field.checked) {
                    return;
                }

                const labelNode = field.closest('label')?.querySelector('span');
                const label = labelNode ? labelNode.textContent.trim() : name.replace(/[_\[\]]+/g, ' ');
                const value = field.tagName === 'SELECT'
                    ? (field.selectedOptions[0]?.textContent || field.value)
                    : field.value;

                if (!value) {
                    return;
                }

                const existing = summary.find((entry) => entry.label === label);
                if (existing) {
                    existing.value += `, ${value}`;
                } else {
                    summary.push({ label, value });
                }
            });

            summaryRoot.innerHTML = summary.map((entry) => `
                <div>
                    <span>${entry.label}</span>
                    <strong>${entry.value}</strong>
                </div>
            `).join('');
        };

        const validatePanel = () => {
            const panel = panels[activeStep - 1];
            if (!panel) {
                return true;
            }

            const required = [...panel.querySelectorAll('[required]')];
            for (const field of required) {
                if ((field.type === 'checkbox' || field.type === 'radio')) {
                    if (!field.checked) {
                        field.focus();
                        alert('Complete the required confirmations before continuing.');
                        return false;
                    }
                    continue;
                }

                if (!String(field.value || '').trim()) {
                    field.focus();
                    alert('Please complete the required fields before continuing.');
                    return false;
                }
            }

            return true;
        };

        const syncStep = () => {
            panels.forEach((panel, index) => panel.classList.toggle('is-active', index + 1 === activeStep));
            steps.forEach((step, index) => step.classList.toggle('is-active', index + 1 === activeStep));
            if (backBtn) {
                backBtn.hidden = activeStep === 1;
            }
            if (nextBtn) {
                nextBtn.hidden = activeStep === panels.length;
            }
            if (submitBtn) {
                submitBtn.hidden = activeStep !== panels.length;
            }
            collectSummary();
        };

        if (backBtn) {
            backBtn.addEventListener('click', () => {
                activeStep = Math.max(1, activeStep - 1);
                syncStep();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                if (!validatePanel()) {
                    return;
                }
                activeStep = Math.min(panels.length, activeStep + 1);
                syncStep();
            });
        }

        steps.forEach((step, index) => {
            step.addEventListener('click', () => {
                if (index + 1 <= activeStep || validatePanel()) {
                    activeStep = index + 1;
                    syncStep();
                }
            });
        });

        if (form) {
            form.addEventListener('change', collectSummary);
            form.addEventListener('input', collectSummary);
            form.addEventListener('submit', (event) => {
                if (event.submitter?.matches('[data-save-draft]')) {
                    return;
                }
                if (!validatePanel()) {
                    event.preventDefault();
                }
            });
        }

        syncStep();
    });
});
