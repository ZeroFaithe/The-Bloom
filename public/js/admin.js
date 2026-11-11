// Staff pages: step rows in the guide form, and the YouTube video picker.
document.addEventListener('DOMContentLoaded', function () {
    setUpSteps();
    setUpVideoPicker();
});

function setUpSteps() {
    var list = document.getElementById('steps');
    var template = document.getElementById('step-template');
    if (!list || !template) return;

    var next = Date.now(); // unique array index for each new row

    document.addEventListener('click', function (event) {
        if (event.target.matches('[data-add-step]')) {
            list.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, String(next++)));
        }
        if (event.target.matches('[data-remove-step]')) {
            var row = event.target.closest('.step-row');
            if (row && list.children.length > 1) row.remove();
        }
    });
}

function setUpVideoPicker() {
    var field = document.getElementById('youtube_video_id');
    var clear = document.querySelector('[data-video-clear]');
    var picker = document.querySelector('[data-video-picker]');
    if (!field) return;

    if (clear) {
        clear.addEventListener('click', function () {
            field.value = '';
            var current = document.querySelector('.video-current');
            if (current) current.remove();
            document.querySelectorAll('.video-result.is-selected').forEach(unselect);
            field.focus();
        });
    }
    if (!picker) return;

    picker.hidden = false; // the search only works with JavaScript
    var input = picker.querySelector('[data-video-query]');
    var button = picker.querySelector('[data-video-search]');
    var status = picker.querySelector('[data-video-status]');
    var list = picker.querySelector('[data-video-results]');

    function unselect(item) {
        item.classList.remove('is-selected');
        item.querySelector('button').textContent = 'Use this video';
    }

    function renderResult(video) {
        var item = document.createElement('li');
        item.className = 'video-result';

        var thumb = document.createElement(video.thumbnail_url ? 'img' : 'span');
        if (video.thumbnail_url) { thumb.src = video.thumbnail_url; thumb.alt = ''; thumb.loading = 'lazy'; }
        else { thumb.className = 'video-thumb-empty'; }

        var text = document.createElement('div');
        var title = document.createElement('p');
        title.className = 'video-title';
        title.textContent = video.title; // textContent, never innerHTML: titles come from YouTube users
        var meta = document.createElement('p');
        meta.className = 'video-meta';
        meta.textContent = video.channel_title;
        text.append(title, meta);

        var choose = document.createElement('button');
        choose.type = 'button';
        choose.className = 'btn btn-secondary';
        choose.textContent = 'Use this video';
        choose.addEventListener('click', function () {
            field.value = video.video_id;
            list.querySelectorAll('.video-result.is-selected').forEach(unselect);
            item.classList.add('is-selected');
            choose.textContent = 'Selected';
            status.textContent = 'Selected "' + video.title + '". Save the guide to keep it.';
        });

        item.append(thumb, text, choose);
        if (field.value === video.video_id) { item.classList.add('is-selected'); choose.textContent = 'Selected'; }
        return item;
    }

    function search() {
        var term = input.value.trim();
        if (!term) { status.textContent = 'Type what to search for, for example "V60 recipe".'; input.focus(); return; }

        button.disabled = true;
        status.textContent = 'Searching YouTube…';
        list.replaceChildren();

        fetch(picker.dataset.endpoint + '?q=' + encodeURIComponent(term), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (response) {
                return response.json().then(function (data) { return { ok: response.ok, data: data }; });
            })
            .then(function (result) {
                if (!result.ok) { status.textContent = result.data.error || 'Search failed. Try again.'; return; }
                var videos = result.data.results || [];
                status.textContent = videos.length ? 'Choose a video for this guide.' : 'No videos found. Try different words.';
                videos.forEach(function (video) { list.appendChild(renderResult(video)); });
            })
            .catch(function () {
                status.textContent = 'Search failed. If you were logged out, log in again and retry.';
            })
            .finally(function () { button.disabled = false; });
    }

    button.addEventListener('click', search);
    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') { event.preventDefault(); search(); } // don't submit the guide form
    });
}
