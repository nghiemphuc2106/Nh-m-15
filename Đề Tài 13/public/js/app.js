/**
 * Link Checker - Frontend JavaScript
 * Giao diện Instrument (Pine / Paper / Ink)
 * Xử lý: modal source pages, re-check AJAX, scope preview, responsive drawer, escape handling
 */

document.addEventListener('DOMContentLoaded', function () {

    // ================================================================
    // SVG Icons Helper for JS
    // ================================================================
    var ICONS = {
        check: '<svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
        alert: '<svg class="icon icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>',
        spinner: '<svg class="icon icon-sm icon-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>'
    };

    // ================================================================
    // Responsive Mobile Sidebar Drawer
    // ================================================================
    var menuToggle = document.getElementById('menuToggle');
    var sidebar = document.getElementById('appSidebar') || document.querySelector('.sidebar');
    var backdrop = document.getElementById('sidebarBackdrop');

    function setSidebarOpen(open) {
        if (!sidebar) return;
        var shouldOpen = (typeof open === 'boolean') ? open : !sidebar.classList.contains('open');
        sidebar.classList.toggle('open', shouldOpen);
        if (backdrop) {
            backdrop.classList.toggle('active', shouldOpen);
        }
        if (menuToggle) {
            menuToggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        }
    }

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            setSidebarOpen();
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', function () {
            setSidebarOpen(false);
        });
    }

    // ================================================================
    // Source Pages Modal
    // ================================================================
    document.querySelectorAll('.btn-sources').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var scanId = this.dataset.scanId;
            var targetUrl = this.dataset.targetUrl;
            showSourcesModal(scanId, targetUrl);
        });
    });

    window.showSourcesModal = function (scanId, targetUrl) {
        var overlay = document.getElementById('sourcesModal');
        if (!overlay) return;

        var body = overlay.querySelector('.modal-body');
        body.innerHTML = '<div class="text-center py-6 text-muted">' + ICONS.spinner + ' Đang tải dữ liệu...</div>';
        overlay.classList.add('active');

        var baseUrl = window.BASE_URL || '';
        fetch(baseUrl + '/scans/' + scanId + '/sources?target=' + encodeURIComponent(targetUrl))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var html = '<div class="mb-4 pb-3" style="border-bottom: 1px solid var(--border);">';
                html += '<span class="text-xs text-muted block mb-1">URL ĐÍCH GẶP SỰ CỐ</span>';
                html += '<div class="url-text font-mono text-sm font-semibold">' + escapeHtml(data.target_url) + '</div>';
                html += '</div>';

                if (data.sources && data.sources.length > 0) {
                    html += '<div class="text-xs text-muted mb-2">DANH SÁCH ' + data.sources.length + ' TRANG CHỨA LIÊN KẾT NÀY:</div>';
                    html += '<div class="table-responsive"><table><thead><tr><th>Trang nguồn</th><th style="width:110px;">Loại</th><th style="width:70px;" class="text-right">Độ sâu</th></tr></thead><tbody>';
                    data.sources.forEach(function (s) {
                        html += '<tr>';
                        html += '<td><a href="' + escapeHtml(s.url) + '" target="_blank" rel="noopener noreferrer" class="url-text font-mono text-sm" style="color:var(--pine);">' + escapeHtml(s.url) + '</a></td>';
                        html += '<td><span class="badge badge-info">' + escapeHtml(s.link_type) + '</span></td>';
                        html += '<td class="text-right font-mono">' + s.depth + '</td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table></div>';
                } else {
                    html += '<p class="text-muted text-center py-4">Không tìm thấy trang nguồn nào trong bản quét này.</p>';
                }

                body.innerHTML = html;
            })
            .catch(function () {
                body.innerHTML = '<p class="text-center text-danger py-4">Có lỗi xảy ra khi tải dữ liệu từ máy chủ.</p>';
            });
    };

    function closeModal(modal) {
        if (!modal) return;
        modal.classList.remove('active');
    }

    // Modal close clicks (button or clicking backdrop)
    document.addEventListener('click', function (e) {
        var closeBtn = e.target.closest('.modal-close');
        if (closeBtn) {
            var modal = closeBtn.closest('.modal-overlay');
            closeModal(modal);
            return;
        }
        if (e.target.classList && e.target.classList.contains('modal-overlay')) {
            closeModal(e.target);
        }
    });

    // Escape key listener for both modal and mobile sidebar
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            var activeModal = document.querySelector('.modal-overlay.active');
            if (activeModal) {
                closeModal(activeModal);
            }
            if (sidebar && sidebar.classList.contains('open')) {
                setSidebarOpen(false);
            }
        }
    });

    // ================================================================
    // Re-check Finding (AJAX)
    // ================================================================
    document.querySelectorAll('.btn-recheck').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var findingId = this.dataset.findingId;
            var row = this.closest('tr');
            var originalHtml = this.innerHTML;

            this.innerHTML = ICONS.spinner + ' Đang kiểm tra...';
            this.disabled = true;

            var baseUrl = window.BASE_URL || '';
            fetch(baseUrl + '/findings/' + findingId + '/recheck', { method: 'POST' })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.status === 'ok') {
                        if (row) row.classList.add('resolved');
                        btn.innerHTML = ICONS.check + ' Đã sửa';
                        btn.className = 'btn btn-sm btn-success';
                        btn.disabled = true;
                    } else {
                        btn.innerHTML = ICONS.alert + ' Vẫn lỗi';
                        btn.className = 'btn btn-sm btn-danger';
                        setTimeout(function () {
                            btn.innerHTML = originalHtml;
                            btn.className = 'btn btn-sm btn-outline btn-recheck';
                            btn.disabled = false;
                        }, 2500);
                    }
                })
                .catch(function () {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                    alert('Lỗi kết nối khi kiểm tra lại liên kết.');
                });
        });
    });

    // ================================================================
    // Confirm Delete Actions
    // ================================================================
    document.querySelectorAll('.btn-delete').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (!confirm('Bạn có chắc chắn muốn xóa mục này? Hành động này không thể hoàn tác.')) {
                e.preventDefault();
            }
        });
    });

    // ================================================================
    // Filter Form Auto Submit
    // ================================================================
    document.querySelectorAll('.filters-bar select').forEach(function (sel) {
        sel.addEventListener('change', function () {
            this.closest('form').submit();
        });
    });

    // ================================================================
    // Scope Preview (Site Create Page)
    // ================================================================
    var seedUrlInput = document.getElementById('seed_url');
    var allowedDomainsInput = document.getElementById('allowed_domains');
    var scopePreview = document.getElementById('scopePreview');

    if (seedUrlInput && scopePreview) {
        function updateScopePreview() {
            var seedUrl = seedUrlInput.value.trim();
            var domains = (allowedDomainsInput ? allowedDomainsInput.value : '').trim();

            if (!seedUrl) {
                scopePreview.innerHTML = '<p class="text-muted text-sm">Nhập URL bắt đầu để xem phân tích phạm vi thu thập.</p>';
                return;
            }

            try {
                var url = new URL(seedUrl);
                var domain = url.hostname + (url.port ? ':' + url.port : '');

                var html = '<div class="scope-summary">';
                html += '<div class="scope-item"><span class="text-muted text-xs block mb-1">URL BẮT ĐẦU:</span><span class="font-mono text-sm font-semibold">' + escapeHtml(seedUrl) + '</span></div>';
                html += '<div class="scope-item mt-2"><span class="text-muted text-xs block mb-1">TÊN MIỀN GỐC (TỰ ĐỘNG CHO PHÉP):</span><span class="badge badge-neutral font-mono">' + escapeHtml(domain) + '</span></div>';

                if (domains) {
                    var extraDomains = domains.split('\n').map(function (d) { return d.trim(); }).filter(Boolean);
                    if (extraDomains.length > 0) {
                        html += '<div class="scope-item mt-2"><span class="text-muted text-xs block mb-1">TÊN MIỀN BỔ SUNG ĐƯỢC PHÉP:</span>';
                        extraDomains.forEach(function (d) {
                            html += '<span class="badge badge-info font-mono" style="margin-right: 6px; margin-bottom: 4px; display: inline-block;">' + escapeHtml(d) + '</span>';
                        });
                        html += '</div>';
                    }
                }

                html += '</div>';
                scopePreview.innerHTML = html;
            } catch (e) {
                scopePreview.innerHTML = '<p class="text-danger text-sm">URL không hợp lệ. Vui lòng nhập đầy đủ tiền tố http:// hoặc https://</p>';
            }
        }

        seedUrlInput.addEventListener('input', updateScopePreview);
        if (allowedDomainsInput) {
            allowedDomainsInput.addEventListener('input', updateScopePreview);
        }
        updateScopePreview();
    }

    // ================================================================
    // Scan Form Submission & Intelligent Progress Animation
    // ================================================================
    var scanForm = document.getElementById('scanForm');
    var btnStartScan = document.getElementById('btnStartScan');
    var scanProgressBox = document.getElementById('scanProgressBox');
    var scanProgressBar = document.getElementById('scanProgressBar');
    var scanProgressPercent = document.getElementById('scanProgressPercent');
    var scanProgressStatus = document.getElementById('scanProgressStatus');
    var maxTotalTimeInput = document.getElementById('max_total_time');

    if (scanForm && btnStartScan && scanProgressBox) {
        scanForm.addEventListener('submit', function (e) {
            // Ngăn chặn submit lặp nếu đang quét
            if (btnStartScan.dataset.submitting === 'true') {
                e.preventDefault();
                return;
            }
            btnStartScan.dataset.submitting = 'true';

            // 1. Đổi nội dung nút thành spinner + "Đang quét..."
            btnStartScan.classList.add('is-loading');
            btnStartScan.innerHTML = ICONS.spinner + ' Đang quét...';

            // Vô hiệu hóa nút sau khi sự kiện submit gửi đi
            setTimeout(function () {
                btnStartScan.disabled = true;
            }, 10);

            // 2. Hiển thị khối Progress Box
            scanProgressBox.style.display = 'flex';

            // 3. Tính toán và chạy Animation Progress Bar mượt mà
            var maxSeconds = maxTotalTimeInput ? (parseInt(maxTotalTimeInput.value, 10) || 60) : 60;
            if (maxSeconds < 10) maxSeconds = 10;

            var currentPercent = 0;
            var startTime = Date.now();

            var statusStages = [
                { threshold: 15, text: 'Đang khởi tạo phiên quét và phân tích URL ban đầu...' },
                { threshold: 38, text: 'Đang thu thập các trang HTML (Crawling) theo độ sâu...' },
                { threshold: 65, text: 'Đang trích xuất liên kết, hình ảnh và chuẩn hóa đường dẫn...' },
                { threshold: 85, text: 'Đang gửi request kiểm tra mã HTTP, anchor, chuyển hướng...' },
                { threshold: 95, text: 'Đang phân loại lỗi và tổng hợp kết quả chi tiết...' },
                { threshold: 99, text: 'Đang hoàn tất báo cáo và chuyển hướng đến trang kết quả...' }
            ];

            function updateStatusText(pct) {
                if (!scanProgressStatus) return;
                for (var i = 0; i < statusStages.length; i++) {
                    if (pct <= statusStages[i].threshold) {
                        scanProgressStatus.textContent = statusStages[i].text;
                        return;
                    }
                }
                scanProgressStatus.textContent = statusStages[statusStages.length - 1].text;
            }

            // Cập nhật nhịp nhàng mỗi 100ms
            var progressInterval = setInterval(function () {
                var elapsedSec = (Date.now() - startTime) / 1000;

                // Thuật toán tiến độ thông minh:
                // - Khởi động nhanh (0 -> 35% trong 2 giây đầu)
                // - Thu thập dữ liệu (35 -> 75% trong 4 giây tiếp theo)
                // - Giảm tốc tiệm cận 95% -> 99% dựa trên maxSeconds trong lúc đợi backend trả kết quả
                var targetPct = 0;
                if (elapsedSec < 2.0) {
                    targetPct = (elapsedSec / 2.0) * 35;
                } else if (elapsedSec < 6.0) {
                    targetPct = 35 + ((elapsedSec - 2.0) / 4.0) * 40;
                } else {
                    var remainingRatio = (elapsedSec - 6.0) / Math.max(8, maxSeconds * 0.7);
                    targetPct = 75 + (24 * (1 - Math.exp(-remainingRatio * 2.2)));
                }

                if (targetPct > 99) targetPct = 99;

                if (targetPct > currentPercent) {
                    currentPercent = Math.min(99, Math.round(targetPct));
                }

                if (scanProgressBar) {
                    scanProgressBar.style.width = currentPercent + '%';
                }
                if (scanProgressPercent) {
                    scanProgressPercent.textContent = currentPercent + '%';
                }
                updateStatusText(currentPercent);
            }, 100);
        });
    }

});

// ================================================================
// Utility Functions
// ================================================================
function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
