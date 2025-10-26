document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-modal-target]').forEach(trigger => {
        trigger.addEventListener('click', () => {
            const target = document.querySelector(trigger.dataset.modalTarget);
            if (target) {
                target.classList.add('active');
            }
        });
    });
    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.modal').classList.remove('active');
        });
    });

    document.querySelectorAll('[data-auto-submit]').forEach(select => {
        select.addEventListener('change', () => {
            select.form.submit();
        });
    });

    const logFrame = document.querySelector('#log-frame');
    if (logFrame) {
        const refreshLogs = () => {
            fetch('/logs/feed').then(res => res.text()).then(text => {
                logFrame.textContent = text;
                logFrame.scrollTop = logFrame.scrollHeight;
            });
        };
        refreshLogs();
        setInterval(refreshLogs, 5000);
    }

    const batchSection = document.querySelector('[data-batch-processing]');
    if (batchSection) {
        const batchId = parseInt(batchSection.dataset.batchId, 10);
        const statusText = document.getElementById('batch-status-text');
        const statusMessage = document.getElementById('batch-status-message');
        const remainingText = document.getElementById('batch-remaining-text');
        const processedText = document.getElementById('batch-processed-text');
        const progressBar = document.getElementById('batch-progress-bar');
        const startBtn = document.getElementById('batch-start-btn');
        const pauseBtn = document.getElementById('batch-pause-btn');
        let total = parseInt(batchSection.dataset.total, 10) || 0;
        let remaining = parseInt(batchSection.dataset.remaining, 10) || 0;
        let processed = parseInt(processedText.textContent, 10) || 0;
        let processing = false;
        let timer = null;

        const updateProgress = () => {
            const percent = total > 0 ? Math.round((processed / total) * 1000) / 10 : 0;
            processedText.textContent = processed;
            remainingText.textContent = remaining;
            progressBar.style.width = percent + '%';
        };

        const showMessage = (message, visible = true) => {
            if (!statusMessage) {
                return;
            }
            if (!message && !visible) {
                statusMessage.style.display = 'none';
                statusMessage.textContent = '';
            } else {
                statusMessage.style.display = 'block';
                statusMessage.textContent = message;
            }
        };

        const scheduleNext = (delayMs) => {
            if (timer) {
                clearTimeout(timer);
            }
            timer = setTimeout(() => {
                runProcessing();
            }, delayMs);
        };

        const handleResponse = (data) => {
            if (data.error) {
                processing = false;
                startBtn.disabled = false;
                showMessage(data.error);
                return;
            }

            switch (data.status) {
                case 'processing':
                    processed += data.processed || 0;
                    remaining = typeof data.remaining === 'number' ? data.remaining : remaining;
                    updateProgress();
                    statusText.textContent = 'processing';
                    showMessage('Zpracovávám dávku…', true);
                    scheduleNext(800);
                    break;
                case 'idle':
                    processed += data.processed || 0;
                    remaining = typeof data.remaining === 'number' ? data.remaining : remaining;
                    updateProgress();
                    statusText.textContent = 'completed';
                    showMessage('Batch dokončen.', true);
                    processing = false;
                    startBtn.disabled = false;
                    break;
                case 'backoff':
                    if (typeof data.remaining === 'number') {
                        remaining = data.remaining;
                    }
                    showMessage(data.message || 'Dočasně čekám na limit API.', true);
                    statusText.textContent = 'backoff';
                    const wait = Math.max(1, (data.wait_seconds || 60)) * 1000;
                    scheduleNext(wait);
                    break;
                case 'halted':
                    processing = false;
                    startBtn.disabled = false;
                    if (typeof data.remaining === 'number') {
                        remaining = data.remaining;
                    }
                    showMessage(data.message || 'Zpracování pozastaveno.', true);
                    statusText.textContent = 'halted';
                    break;
                case 'error':
                    processing = false;
                    startBtn.disabled = false;
                    if (typeof data.remaining === 'number') {
                        remaining = data.remaining;
                    }
                    showMessage(data.message || 'Chyba zpracování.', true);
                    statusText.textContent = 'error';
                    break;
                default:
                    processing = false;
                    startBtn.disabled = false;
                    showMessage('Neznámý stav zpracování.', true);
            }
        };

        const runProcessing = () => {
            if (!processing) {
                return;
            }
            fetch(`/batches/${batchId}/process`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(res => res.json())
                .then(handleResponse)
                .catch(() => {
                    processing = false;
                    startBtn.disabled = false;
                    showMessage('Chyba při komunikaci se serverem.', true);
                });
        };

        startBtn.addEventListener('click', () => {
            if (processing) {
                return;
            }
            processing = true;
            startBtn.disabled = true;
            showMessage('Spouštím zpracování…', true);
            fetch(`/batches/${batchId}/resume`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(() => {
                    statusText.textContent = 'processing';
                    runProcessing();
                })
                .catch(() => {
                    processing = false;
                    startBtn.disabled = false;
                    showMessage('Nepodařilo se nastavit stav batche.', true);
                });
        });

        pauseBtn.addEventListener('click', () => {
            processing = false;
            startBtn.disabled = false;
            if (timer) {
                clearTimeout(timer);
            }
            fetch(`/batches/${batchId}/pause`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(() => {
                    statusText.textContent = 'halted';
                    showMessage('Batch byl pozastaven.', true);
                })
                .catch(() => {
                    showMessage('Nepodařilo se pozastavit batch.', true);
                });
        });

        updateProgress();
    }
});
