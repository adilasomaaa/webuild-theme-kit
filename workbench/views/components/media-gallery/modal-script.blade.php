<script>
    (() => {
        if (window.mediaGalleryModalInitialized) return;
        window.mediaGalleryModalInitialized = true;

        let galleryItems = [];
        let activeIndex = 0;
        let touchStart = null;

        const modal = document.getElementById('media-modal');
        const container = document.getElementById('media-modal-container');
        const titleElement = document.getElementById('media-modal-title');
        const descriptionElement = document.getElementById('media-modal-desc');
        const countElement = document.getElementById('media-modal-count');
        const previousButton = document.getElementById('media-modal-prev');
        const nextButton = document.getElementById('media-modal-next');

        const renderActiveItem = () => {
            const item = galleryItems[activeIndex];
            if (!modal || !container || !item) return;

            titleElement.textContent = item.title;
            descriptionElement.textContent = item.description;
            container.querySelectorAll('img, video, iframe').forEach((mediaElement) => mediaElement.remove());

            if (item.type === 'video') {
                const videoTarget = item.embedUrl || item.url;
                const youtubeId = videoTarget.match(/(?:youtube\.com\/(?:watch\?.*v=|embed\/)|youtu\.be\/)([\w-]+)/)?.[1];

                if (youtubeId) {
                    const iframe = document.createElement('iframe');
                    iframe.src = `https://www.youtube.com/embed/${youtubeId}?autoplay=1`;
                    iframe.title = item.title || 'Video';
                    iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
                    iframe.allowFullscreen = true;
                    iframe.className = 'absolute inset-0 h-full w-full border-0';
                    container.append(iframe);
                } else if (item.url) {
                    const video = document.createElement('video');
                    const source = document.createElement('source');
                    source.src = item.url;
                    source.type = 'video/mp4';
                    video.controls = true;
                    video.autoplay = true;
                    video.playsInline = true;
                    video.className = 'h-full w-full object-contain';
                    video.append(source);
                    container.append(video);
                }
            } else {
                const image = document.createElement('img');
                image.src = item.url || item.embedUrl;
                image.alt = item.title || 'Pratinjau gambar';
                image.draggable = false;
                image.className = 'h-full w-full select-none object-contain';
                container.append(image);
            }

            const hasMultipleItems = galleryItems.length > 1;
            previousButton.classList.toggle('hidden', !hasMultipleItems);
            previousButton.classList.toggle('flex', hasMultipleItems);
            nextButton.classList.toggle('hidden', !hasMultipleItems);
            nextButton.classList.toggle('flex', hasMultipleItems);
            countElement.textContent = hasMultipleItems ? `${activeIndex + 1} / ${galleryItems.length}` : '';
            countElement.classList.toggle('hidden', !hasMultipleItems);
        };

        const moveBy = (direction) => {
            if (galleryItems.length < 2) return;
            activeIndex = (activeIndex + direction + galleryItems.length) % galleryItems.length;
            renderActiveItem();
        };

        window.openMediaModal = (titleOrElement, type, fileUrl, embedUrl, description) => {
            const selectedElement = titleOrElement instanceof HTMLElement ? titleOrElement : null;

            if (selectedElement) {
                const gallery = selectedElement.closest('[data-media-gallery-scope]')
                    || selectedElement.closest('section')
                    || document;
                const seen = new Set();
                galleryItems = Array.from(gallery.querySelectorAll('[data-media-type][data-media-url]'))
                    .map((element) => ({
                        element,
                        type: element.getAttribute('data-media-type') || 'photo',
                        title: element.getAttribute('data-media-title') || '',
                        url: element.getAttribute('data-media-url') || '',
                        embedUrl: element.getAttribute('data-media-embed') || '',
                        description: element.getAttribute('data-media-desc') || '',
                    }))
                    .filter((item) => {
                        const key = `${item.type}|${item.url}|${item.embedUrl}`;
                        if ((!item.url && !item.embedUrl) || seen.has(key)) return false;
                        seen.add(key);
                        return true;
                    });
                activeIndex = Math.max(0, galleryItems.findIndex((item) => item.element === selectedElement));
            } else {
                galleryItems = [{
                    type: type || 'photo',
                    title: String(titleOrElement || ''),
                    url: fileUrl || '',
                    embedUrl: embedUrl || '',
                    description: description || '',
                }];
                activeIndex = 0;
            }

            if (galleryItems.length === 0) return;
            renderActiveItem();
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        };

        window.closeMediaModal = () => {
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            container.querySelectorAll('img, video, iframe').forEach((mediaElement) => mediaElement.remove());
            document.body.style.overflow = '';
            galleryItems = [];
            activeIndex = 0;
        };

        previousButton.addEventListener('click', () => moveBy(-1));
        nextButton.addEventListener('click', () => moveBy(1));
        container.addEventListener('touchstart', (event) => {
            if (event.target instanceof Element && event.target.closest('button, video, iframe')) {
                touchStart = null;

                return;
            }

            const touch = event.changedTouches[0];
            touchStart = { x: touch.clientX, y: touch.clientY };
        }, { passive: true });
        container.addEventListener('touchend', (event) => {
            if (!touchStart) return;
            const touch = event.changedTouches[0];
            const deltaX = touch.clientX - touchStart.x;
            const deltaY = touch.clientY - touchStart.y;
            touchStart = null;

            if (Math.abs(deltaX) > 48 && Math.abs(deltaX) > Math.abs(deltaY) * 1.25) {
                moveBy(deltaX < 0 ? 1 : -1);
            }
        }, { passive: true });
        document.addEventListener('keydown', (event) => {
            if (!modal || modal.classList.contains('hidden')) return;
            if (event.key === 'Escape') window.closeMediaModal();
            if (event.key === 'ArrowLeft') moveBy(-1);
            if (event.key === 'ArrowRight') moveBy(1);
        });
    })();
</script>
