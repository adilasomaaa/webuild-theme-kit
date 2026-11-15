{{--
    Drawer customizer workbench (khusus pengembangan tema).
    Disisipkan oleh App\Http\Middleware\InjectCustomizerDrawer; tidak menjadi bagian dari view tema.
    Dirender dalam Shadow DOM agar CSS tema dan drawer tidak saling memengaruhi.
--}}
<div id="wb-customizer-host"></div>
<script type="application/json" id="wb-customizer-data">{!! json_encode($customizerPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
<script>
(() => {
    const payload = JSON.parse(document.getElementById('wb-customizer-data').textContent);
    const { schema, pageKey, routeName } = payload;
    let draft = structuredClone(payload.state);
    let dirty = false;

    const store = {
        get(key, fallback) { try { return JSON.parse(localStorage.getItem('wb-customizer:' + key)) ?? fallback; } catch { return fallback; } },
        set(key, value) { try { localStorage.setItem('wb-customizer:' + key, JSON.stringify(value)); } catch {} },
    };
    let open = store.get('open', false);
    let tab = store.get('tab', 'page');
    let selectedPage = pageKey ?? store.get('page', null);

    const host = document.getElementById('wb-customizer-host');
    const root = host.attachShadow({ mode: 'open' });
    root.innerHTML = `
<style>
    :host { all: initial; }
    * { box-sizing: border-box; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; }
    .fab { position: fixed; right: 16px; bottom: 16px; z-index: 2147483000; width: 48px; height: 48px; border-radius: 999px; border: 0; background: #0f172a; color: #fff; display: grid; place-items: center; cursor: pointer; box-shadow: 0 8px 24px rgb(15 23 42 / .3); }
    .fab:hover { background: #1e293b; }
    .fab .dot { position: absolute; top: 6px; right: 6px; width: 10px; height: 10px; border-radius: 999px; background: #f59e0b; border: 2px solid #0f172a; }
    .backdrop { position: fixed; inset: 0; z-index: 2147483001; background: rgb(15 23 42 / .35); }
    .drawer { position: fixed; top: 0; right: 0; bottom: 0; z-index: 2147483002; width: min(400px, 100vw); background: #fff; color: #0f172a; display: flex; flex-direction: column; box-shadow: -12px 0 32px rgb(15 23 42 / .18); font-size: 13px; line-height: 1.45; }
    [hidden] { display: none !important; }
    .head { padding: 14px 16px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 8px; }
    .head h2 { margin: 0; font-size: 15px; font-weight: 700; flex: 1; }
    .head small { display: block; font-weight: 400; color: #64748b; font-size: 12px; }
    .tabs { display: flex; gap: 2px; padding: 8px 12px 0; border-bottom: 1px solid #e2e8f0; overflow-x: auto; }
    .tabs button { border: 0; background: none; padding: 8px 10px; font-size: 12.5px; font-weight: 600; color: #64748b; border-bottom: 2px solid transparent; cursor: pointer; white-space: nowrap; }
    .tabs button[aria-selected="true"] { color: #0f172a; border-color: #0f172a; }
    .body { position: relative; flex: 1; overflow-y: auto; padding: 14px 16px 24px; }
    .foot { padding: 12px 16px; border-top: 1px solid #e2e8f0; display: flex; align-items: center; gap: 8px; }
    .foot .status { flex: 1; color: #b45309; font-size: 12px; }
    .btn { border: 1px solid #cbd5e1; background: #fff; color: #0f172a; border-radius: 8px; padding: 7px 12px; font-size: 12.5px; font-weight: 600; cursor: pointer; }
    .btn:hover { background: #f8fafc; }
    .btn.primary { background: #0f172a; border-color: #0f172a; color: #fff; }
    .btn.primary:hover { background: #1e293b; }
    .btn.block { width: 100%; }
    .btn:disabled { opacity: .5; cursor: default; }
    .icon-btn { border: 0; background: none; width: 26px; height: 26px; border-radius: 6px; color: #64748b; cursor: pointer; display: inline-grid; place-items: center; font-size: 14px; flex-shrink: 0; }
    .icon-btn:hover { background: #f1f5f9; color: #0f172a; }
    h3 { margin: 18px 0 8px; font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #64748b; font-weight: 700; }
    h3:first-child { margin-top: 0; }
    .hint { color: #64748b; font-size: 12px; margin: 0 0 12px; }
    .card { border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 8px; background: #fff; }
    .card.off { background: #f8fafc; }
    .card.off .name { color: #94a3b8; }
    .card.dragging { opacity: .35; }
    .row { display: flex; align-items: center; gap: 6px; padding: 8px 8px 8px 4px; }
    .handle { cursor: grab; touch-action: none; color: #94a3b8; width: 20px; height: 26px; display: inline-grid; place-items: center; border-radius: 6px; flex-shrink: 0; user-select: none; }
    .handle:hover { color: #0f172a; background: #f1f5f9; }
    .handle:active { cursor: grabbing; }
    .name { flex: 1; font-weight: 600; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .name small { font-weight: 400; color: #64748b; margin-left: 4px; }
    .meta { display: block; font-weight: 400; font-size: 11.5px; color: #64748b; overflow: hidden; text-overflow: ellipsis; }
    .details { padding: 4px 10px 10px; border-top: 1px dashed #e2e8f0; display: grid; gap: 10px; }
    .group-title { margin-top: 6px; padding-top: 8px; border-top: 1px solid #f1f5f9; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #0f766e; }
    .group-title:first-child { margin-top: 0; border-top: 0; padding-top: 4px; }
    label.field, div.field { display: grid; gap: 4px; font-weight: 600; font-size: 12px; color: #334155; }
    .field-help { font-weight: 400; font-size: 11.5px; color: #64748b; }
    label.check { display: flex; align-items: center; gap: 8px; font-weight: 500; font-size: 12.5px; color: #334155; cursor: pointer; }
    input[type=text], input[type=url], input[type=number], select, textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 7px; padding: 6px 8px; font-size: 12.5px; color: #0f172a; background: #fff; font-weight: 400; }
    textarea { min-height: 64px; resize: vertical; }
    textarea.code { font-family: ui-monospace, monospace; font-size: 11.5px; }
    input:focus, select:focus, textarea:focus, button:focus-visible { outline: 2px solid #0f172a; outline-offset: 1px; }
    input[type=range] { width: 100%; accent-color: #0f172a; }
    .inline { display: flex; align-items: center; gap: 8px; }
    .inline input[type=color] { width: 40px; height: 32px; padding: 2px; border: 1px solid #cbd5e1; border-radius: 7px; background: #fff; cursor: pointer; flex-shrink: 0; }
    .inline input[type=text] { width: 110px; font-family: ui-monospace, monospace; text-transform: uppercase; }
    .range-value { font-family: ui-monospace, monospace; font-size: 12px; color: #0f172a; min-width: 40px; text-align: right; }
    .preview { width: 100%; height: 96px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; background: #f1f5f9; }
    .switch { appearance: none; width: 30px; height: 18px; border-radius: 999px; background: #cbd5e1; position: relative; cursor: pointer; flex-shrink: 0; margin: 0; transition: background .15s; }
    .switch::after { content: ""; position: absolute; top: 2px; left: 2px; width: 14px; height: 14px; border-radius: 999px; background: #fff; transition: transform .15s; }
    .switch:checked { background: #059669; }
    .switch:checked::after { transform: translateX(12px); }
    .segmented { display: flex; flex-wrap: wrap; gap: 4px; }
    .segmented button { border: 1px solid #cbd5e1; background: #fff; border-radius: 7px; padding: 5px 9px; font-size: 12px; cursor: pointer; color: #334155; }
    .segmented button[aria-pressed="true"] { background: #0f172a; color: #fff; border-color: #0f172a; }
    .column { border: 1px solid #e2e8f0; border-radius: 12px; padding: 10px; margin-bottom: 12px; background: #fcfcfd; }
    .column-title { font-weight: 700; margin-bottom: 8px; }
    .sort-list { min-height: 8px; }
    .sort-list.empty-drop { min-height: 40px; border: 1px dashed #cbd5e1; border-radius: 8px; margin-bottom: 8px; }
    .add { display: flex; gap: 6px; }
    .menu-children { margin: 0 8px 8px 26px; }
    .empty { color: #94a3b8; font-size: 12px; padding: 4px 2px 8px; }
    .error { color: #dc2626; font-size: 11.5px; font-weight: 500; }
    .drop-indicator { position: absolute; height: 3px; border-radius: 3px; background: #059669; pointer-events: none; z-index: 5; box-shadow: 0 0 0 2px rgb(5 150 105 / .2); }
    .modal-wrap { position: fixed; inset: 0; z-index: 2147483003; background: rgb(15 23 42 / .55); display: grid; place-items: center; padding: 16px; }
    .modal { width: min(560px, 100%); max-height: calc(100dvh - 32px); overflow-y: auto; background: #fff; color: #0f172a; border-radius: 14px; padding: 28px 28px 24px; position: relative; box-shadow: 0 24px 48px rgb(15 23 42 / .25); font-size: 13px; }
    .modal h2 { margin: 0 32px 6px 0; font-size: 22px; font-weight: 700; letter-spacing: -.01em; }
    .modal .sub { margin: 0 0 22px; color: #64748b; font-size: 14px; line-height: 1.5; }
    .modal .close-modal { position: absolute; top: 18px; right: 18px; }
    .modal form { display: grid; gap: 16px; }
    .modal label.field { font-size: 13px; }
    .modal input[type=text], .modal select { padding: 10px 12px; font-size: 13.5px; border-radius: 9px; }
    .modal .actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 6px; }
    .modal .actions .btn { padding: 10px 18px; font-size: 13.5px; }
    .section-card { background: #fcfcfd; }
    .section-card > .section-head { border-bottom: 1px solid #f1f5f9; }
    .section-cols { padding: 8px 8px 6px; }
    .col-card { margin-bottom: 6px; }
    .col-card .row { padding: 6px 6px 6px 2px; }
    .col-label { font-size: 11.5px; font-weight: 700; color: #64748b; white-space: nowrap; }
    select.span-select { width: auto; padding: 3px 4px; font-size: 11.5px; }
    .btn.pick { flex: 1; padding: 5px 8px; font-size: 12px; border-style: dashed; color: #0f766e; }
    .link-btn { border: 0; background: none; color: #0f766e; font-size: 12px; font-weight: 600; cursor: pointer; padding: 2px 2px 4px; }
    .link-btn:hover { text-decoration: underline; }
    small.warn { color: #b45309; }
    .layout-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; }
    .layout-option { display: grid; gap: 8px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; padding: 12px; cursor: pointer; font-size: 12.5px; font-weight: 600; color: #334155; text-align: left; }
    .layout-option:hover { border-color: #0f172a; }
    .layout-bars { display: flex; gap: 4px; height: 28px; }
    .layout-bars span { background: #e2e8f0; border-radius: 4px; }
    .layout-option:hover .layout-bars span { background: #99f6e4; }
    .picker { display: grid; gap: 10px; }
    .picker input { padding: 10px 12px; font-size: 13.5px; border-radius: 9px; }
    .picker-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 8px; max-height: min(55dvh, 460px); overflow-y: auto; padding: 2px; }
    .picker-item { display: grid; gap: 2px; text-align: left; border: 1px solid #e2e8f0; border-radius: 9px; background: #fff; padding: 10px 12px; cursor: pointer; }
    .picker-item:hover { border-color: #0f172a; background: #f8fafc; }
    .picker-item strong { font-size: 13px; color: #0f172a; }
    .picker-item span { font-size: 11.5px; color: #64748b; }
    .palette-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(104px, 1fr)); gap: 8px; }
    .palette { display: grid; gap: 6px; border: 1px solid #e2e8f0; border-radius: 9px; background: #fff; padding: 8px; cursor: pointer; font-size: 12px; font-weight: 600; color: #334155; text-align: left; }
    .palette:hover { border-color: #94a3b8; }
    .palette.active { border-color: #0f172a; box-shadow: 0 0 0 1px #0f172a; }
    .swatches { display: flex; height: 20px; border-radius: 5px; overflow: hidden; }
    .swatches span { flex: 1; }
    .resolved { font-weight: 400; font-size: 11.5px; color: #64748b; font-family: ui-monospace, monospace; }
</style>
<button class="fab" type="button" aria-label="Buka customizer tema" title="Customizer tema (workbench)">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
    <span class="dot" hidden></span>
</button>
<div class="backdrop" hidden></div>
<aside class="drawer" role="dialog" aria-modal="true" aria-labelledby="wb-title" hidden>
    <div class="head">
        <h2 id="wb-title">Customizer Tema<small></small></h2>
        <button class="icon-btn close" type="button" aria-label="Tutup">✕</button>
    </div>
    <div class="tabs" role="tablist"></div>
    <div class="body"></div>
    <div class="foot">
        <span class="status"></span>
        <button class="btn reset" type="button">Reset default</button>
        <button class="btn primary save" type="button">Simpan</button>
    </div>
</aside>
<div class="modal-host"></div>`;

    const $ = (selector) => root.querySelector(selector);
    const fab = $('.fab'), backdrop = $('.backdrop'), drawer = $('.drawer'), body = $('.body'), modalHost = $('.modal-host');
    $('.head small').textContent = `${schema.theme.name} · ${routeName ?? 'halaman'}`;

    const TABS = [
        ['page', 'Halaman ini'],
        ['colors', 'Warna'],
        ['components', 'Section'],
        ['header', 'Header'],
        ['footer', 'Footer'],
        ['menu', 'Menu'],
    ];

    function h(tag, attrs = {}, ...children) {
        const el = document.createElement(tag);
        for (const [key, value] of Object.entries(attrs)) {
            if (value === undefined || value === null || value === false) continue;
            if (key.startsWith('on')) el.addEventListener(key.slice(2), value);
            else if (key === 'checked' || key === 'value' || key === 'disabled') el[key] = value;
            else el.setAttribute(key, value === true ? '' : value);
        }
        for (const child of children.flat()) {
            if (child !== null && child !== undefined && child !== false) el.append(child);
        }
        return el;
    }

    function markDirty(rerender = false) {
        dirty = true;
        $('.status').textContent = 'Perubahan belum disimpan';
        fab.querySelector('.dot').hidden = false;
        if (rerender) render();
    }

    const optionLabel = (text, key) => (typeof text === 'object' ? (text.label ?? key) : text);

    // ---- Field generik untuk component_settings dan pengaturan halaman ----
    function field(name, def, value, onChange) {
        const label = def.label ?? name;
        const type = def.type ?? 'string';
        const help = def.help ? h('span', { class: 'field-help' }, def.help) : null;
        const changeAndRender = (v) => { onChange(v); render(); };

        if (type === 'boolean') {
            return h('div', { class: 'field' },
                h('label', { class: 'check' },
                    h('input', { type: 'checkbox', class: 'switch', checked: !!value, onchange: (e) => changeAndRender(e.target.checked) }),
                    label),
                help);
        }
        if (type === 'select') {
            const options = Object.entries(def.options ?? {});
            if (options.length <= 4) {
                return h('div', { class: 'field' },
                    h('span', {}, label),
                    h('div', { class: 'segmented', role: 'group', 'aria-label': label },
                        options.map(([key, text]) => h('button', {
                            type: 'button', 'aria-pressed': String(String(value) === key),
                            onclick: () => changeAndRender(key),
                        }, optionLabel(text, key)))),
                    help);
            }
            return h('label', { class: 'field' }, label,
                h('select', { onchange: (e) => changeAndRender(e.target.value) },
                    options.map(([key, text]) => h('option', { value: key, selected: String(value) === key }, optionLabel(text, key)))),
                help);
        }
        if (type === 'color') {
            const hex = h('input', { type: 'text', value: value ?? '#ffffff', 'aria-label': `${label} (hex)`, maxlength: 7 });
            const picker = h('input', { type: 'color', value: /^#[0-9a-f]{6}$/i.test(value ?? '') ? value : '#ffffff', 'aria-label': label });
            picker.addEventListener('input', () => { hex.value = picker.value; onChange(picker.value); });
            hex.addEventListener('change', () => {
                if (/^#[0-9a-f]{6}$/i.test(hex.value)) { picker.value = hex.value; onChange(hex.value.toLowerCase()); }
                else hex.value = picker.value;
            });
            return h('div', { class: 'field' }, h('span', {}, label), h('div', { class: 'inline' }, picker, hex), help);
        }
        if (type === 'range') {
            const unit = def.unit ?? '';
            const current = value ?? def.default ?? def.min ?? 0;
            const output = h('span', { class: 'range-value' }, `${current}${unit}`);
            return h('div', { class: 'field' },
                h('div', { class: 'inline' }, h('span', { style: 'flex: 1' }, label), output),
                h('input', {
                    type: 'range', min: def.min ?? 0, max: def.max ?? 100, step: def.step ?? 1, value: current, 'aria-label': label,
                    oninput: (e) => { output.textContent = `${e.target.value}${unit}`; onChange(Number(e.target.value)); },
                }),
                help);
        }
        if (type === 'image') {
            const preview = h('img', { class: 'preview', src: value || '', alt: '', hidden: !value });
            return h('label', { class: 'field' }, label,
                h('input', {
                    type: 'url', value: value ?? '', placeholder: def.placeholder ?? 'https://...',
                    onchange: (e) => { onChange(e.target.value); preview.src = e.target.value; preview.hidden = !e.target.value; },
                }),
                preview, help);
        }
        if (type === 'array' || type === 'json') {
            const error = h('span', { class: 'error' });
            return h('label', { class: 'field' }, `${label} (JSON)`,
                h('textarea', {
                    class: 'code',
                    value: JSON.stringify(value ?? def.default ?? [], null, 2),
                    onchange: (e) => {
                        try { onChange(JSON.parse(e.target.value || '[]')); error.textContent = ''; }
                        catch { error.textContent = 'JSON tidak valid, perubahan tidak dipakai.'; }
                    },
                }), error, help);
        }
        if (type === 'text') {
            return h('label', { class: 'field' }, label,
                h('textarea', { value: value ?? '', placeholder: def.placeholder ?? '', oninput: (e) => onChange(e.target.value) }), help);
        }
        const numeric = type === 'number' || type === 'integer';
        return h('label', { class: 'field' }, label,
            h('input', {
                type: numeric ? 'number' : (type === 'url' ? 'url' : 'text'),
                value: value ?? '', placeholder: def.placeholder ?? '',
                oninput: (e) => onChange(numeric ? Number(e.target.value) : e.target.value),
            }), help);
    }

    // Field dengan 'group' (judul kelompok) dan 'show_if' (tampil bila field lain bernilai tertentu).
    function fieldList(fields, values, onChange) {
        const current = (key) => values[key] ?? fields[key]?.default;
        const visible = (def) => Object.entries(def.show_if ?? {}).every(([key, expected]) =>
            Array.isArray(expected) ? expected.includes(current(key)) : current(key) === expected);
        const out = [];
        let group = null;
        for (const [key, def] of Object.entries(fields)) {
            if (!visible(def)) continue;
            if (def.group && def.group !== group) {
                group = def.group;
                out.push(h('div', { class: 'group-title' }, group));
            }
            out.push(def.type === 'repeater'
                ? repeaterField(key, def, values)
                : field(key, def, current(key), (value) => onChange(key, value)));
        }
        return out;
    }

    // Daftar item berulang (mis. kartu pada card_grid): tambah, hapus, geser, dan ubah tiap item.
    function repeaterField(key, def, values) {
        if (!Array.isArray(values[key])) values[key] = structuredClone(def.default ?? []);
        const items = values[key];
        const blank = () => Object.fromEntries(Object.entries(def.fields).map(([k, f]) => [k, structuredClone(f.default ?? '')]));
        return h('div', { class: 'field' },
            h('span', {}, `${def.label ?? key} (${items.length})`),
            sortList(items, `rep-${key}`, 0, (listId) => items.map((item, index) => {
                const isOpen = expanded.has(item);
                return h('div', { class: 'card', 'data-sort-item': true },
                    h('div', { class: 'row' },
                        handle(listId, index),
                        h('span', { class: 'name' }, item[def.item_label] || `Item ${index + 1}`),
                        h('button', { type: 'button', class: 'icon-btn', 'aria-label': 'Ubah item', 'aria-expanded': String(isOpen), onclick: () => { toggle(item); } }, isOpen ? '▾' : '▸'),
                        h('button', { type: 'button', class: 'icon-btn', 'aria-label': 'Hapus item', onclick: () => { items.splice(index, 1); markDirty(true); } }, '✕')),
                    isOpen ? h('div', { class: 'details' }, fieldList(def.fields, item, (k, v) => { item[k] = v; markDirty(); })) : null);
            })),
            h('button', { type: 'button', class: 'btn', onclick: () => { const item = blank(); items.push(item); expanded.add(item); markDirty(true); } }, `+ ${def.add_label ?? 'Tambah item'}`));
    }

    // ---- Drag & drop (pointer events, juga berfungsi di layar sentuh) ----
    let sortLists = new Map();
    let drag = null;
    const indicator = h('div', { class: 'drop-indicator', hidden: true });

    // List didaftarkan sebelum item dibuat, karena item bisa berisi list lain (kolom, repeater, submenu).
    function sortList(list, group, depth, buildItems, attrs = {}) {
        const id = String(sortLists.size);
        sortLists.set(id, list);
        const { class: extraClass = '', ...rest } = attrs;
        return h('div', { class: `sort-list ${extraClass}` + (list.length ? '' : ' empty-drop'), 'data-list': id, 'data-group': group, 'data-depth': depth, ...rest }, buildItems(id));
    }

    function handle(listId, index) {
        return h('span', {
            class: 'handle', role: 'button', 'aria-label': 'Geser untuk mengurutkan', title: 'Geser untuk mengurutkan',
            onpointerdown: (e) => startDrag(e, listId, index),
        }, '⋮⋮');
    }

    function startDrag(e, listId, index) {
        if (e.button !== 0) return;
        e.preventDefault();
        const item = e.currentTarget.closest('[data-sort-item]');
        const listEl = item.closest('.sort-list');
        e.currentTarget.setPointerCapture(e.pointerId);
        drag = { listId, index, item, group: listEl.dataset.group, target: null, x: e.clientX, y: e.clientY };
        item.classList.add('dragging');
        body.append(indicator);
        requestAnimationFrame(autoScroll);
        e.currentTarget.addEventListener('pointermove', moveDrag);
        e.currentTarget.addEventListener('pointerup', endDrag, { once: true });
        e.currentTarget.addEventListener('pointercancel', cancelDrag, { once: true });
    }

    // Selama pointer ditahan di tepi atas/bawah drawer, daftar terus bergulir meski pointer diam.
    function autoScroll() {
        if (!drag) return;
        const bodyRect = body.getBoundingClientRect();
        // Baru aktif setelah pointer digerakkan, agar item di tepi tidak langsung menggulir saat ditekan.
        const edge = !drag.moved ? 0 : drag.y < bodyRect.top + 48 ? -1 : (drag.y > bodyRect.bottom - 48 ? 1 : 0);
        if (edge) {
            const before = body.scrollTop;
            body.scrollTop += edge * 14;
            if (body.scrollTop !== before) updateTarget();
        }
        requestAnimationFrame(autoScroll);
    }

    function moveDrag(e) {
        if (!drag) return;
        drag.x = e.clientX;
        drag.y = e.clientY;
        drag.moved = true;
        updateTarget();
    }

    function updateTarget() {
        const bodyRect = body.getBoundingClientRect();
        drag.target = null;
        indicator.hidden = true;
        const under = root.elementFromPoint(drag.x, drag.y);
        // Naik dari list terdalam sampai menemukan list dengan grup yang sama (list bisa bersarang).
        let listEl = under?.closest('.sort-list');
        while (listEl && listEl.dataset.group !== drag.group) listEl = listEl.parentElement?.closest('.sort-list');
        if (!listEl || drag.item.contains(listEl)) return;
        const dragged = sortLists.get(drag.listId)[drag.index];
        if (Number(listEl.dataset.depth) > 0 && dragged.children?.length) return; // menu induk tidak bisa jadi submenu

        const items = [...listEl.children].filter((el) => el.matches('[data-sort-item]'));
        let index = items.length;
        let anchor = null, after = true;
        for (const [i, el] of items.entries()) {
            const rect = el.getBoundingClientRect();
            if (drag.y < rect.top + rect.height / 2) { index = i; anchor = el; after = false; break; }
            anchor = el;
        }
        drag.target = { listId: listEl.dataset.list, index };

        const ref = (anchor ?? listEl).getBoundingClientRect();
        const top = anchor ? (after ? ref.bottom + 3 : ref.top - 5) : ref.top + ref.height / 2;
        Object.assign(indicator.style, {
            top: `${top - bodyRect.top + body.scrollTop}px`,
            left: `${ref.left - bodyRect.left}px`,
            width: `${ref.width}px`,
        });
        indicator.hidden = false;
    }

    function endDrag(e) {
        e.currentTarget.removeEventListener('pointermove', moveDrag);
        const state = drag;
        cancelDrag();
        if (!state?.target) return;
        const from = sortLists.get(state.listId), to = sortLists.get(state.target.listId);
        let insertAt = state.target.index;
        if (from === to && state.index < insertAt) insertAt--;
        if (from === to && insertAt === state.index) return;
        const [moved] = from.splice(state.index, 1);
        to.splice(insertAt, 0, moved);
        markDirty(true);
    }

    function cancelDrag() {
        drag?.item.classList.remove('dragging');
        indicator.hidden = true;
        indicator.remove();
        drag = null;
    }

    // ---- Komponen ----
    // Objek (komponen, kolom, item repeater) yang sedang dibuka; identitas objek tetap selama draft sama.
    const expanded = new WeakSet();
    const toggle = (obj) => { expanded.has(obj) ? expanded.delete(obj) : expanded.add(obj); render(); };
    const uid = (prefix) => prefix + Math.random().toString(36).slice(2, 10);
    const componentMeta = (type) => schema.components[type] ?? { label: type, variants: {}, settings: {}, variant_key: 'layout_variant', default_configuration: {} };
    const newConfiguration = (type) => structuredClone(componentMeta(type).default_configuration ?? {});

    // Isi detail komponen: varian lalu atribut. Mengembalikan null bila komponen tidak punya pengaturan.
    function componentDetails(item) {
        const meta = componentMeta(item.section_type);
        // PHP meng-encode konfigurasi kosong sebagai [], padahal kuncinya harus tersimpan sebagai objek.
        if (!item.configuration || Array.isArray(item.configuration)) item.configuration = {};
        const config = item.configuration;
        const variantEntries = Object.entries(meta.variants ?? {});
        const settings = Object.fromEntries(Object.entries(meta.settings ?? {}).filter(([key]) => key !== meta.variant_key || variantEntries.length === 0));
        if (!variantEntries.length && !Object.keys(settings).length) return null;
        return [
            variantEntries.length ? field('variant', { type: 'select', label: 'Varian', options: meta.variants }, config[meta.variant_key] ?? variantEntries[0][0], (value) => { config[meta.variant_key] = value; markDirty(); }) : null,
            ...fieldList(settings, config, (key, value) => { config[key] = value; markDirty(); }),
        ];
    }

    function variantLabel(item) {
        const meta = componentMeta(item.section_type);
        const current = item.configuration?.[meta.variant_key];
        return current && meta.variants?.[current] ? h('small', {}, `· ${meta.variants[current]}`) : null;
    }

    // Komponen di kolom header/footer.
    function areaCard(item, listId, index) {
        const meta = componentMeta(item.section_type);
        const details = expanded.has(item) ? componentDetails(item) : null;
        const hasDetails = componentDetails(item) !== null;
        return h('div', { class: 'card' + (item.is_enabled ? '' : ' off'), 'data-sort-item': true },
            h('div', { class: 'row' },
                handle(listId, index),
                h('input', {
                    type: 'checkbox', class: 'switch', checked: item.is_enabled, 'aria-label': `Tampilkan ${meta.label}`,
                    onchange: (e) => { item.is_enabled = e.target.checked; markDirty(true); },
                }),
                h('span', { class: 'name', title: item.section_type }, meta.label, variantLabel(item)),
                hasDetails ? h('button', { type: 'button', class: 'icon-btn', 'aria-label': 'Pengaturan', 'aria-expanded': String(expanded.has(item)), onclick: () => toggle(item) }, expanded.has(item) ? '▾' : '▸') : null,
                h('button', { type: 'button', class: 'icon-btn', 'aria-label': 'Hapus', onclick: () => { sortLists.get(listId).splice(index, 1); markDirty(true); } }, '✕')),
            details ? h('div', { class: 'details' }, details) : null);
    }

    function columnsPanel(kind) {
        return [
            h('p', { class: 'hint' }, `Atur komponen di tiap kolom ${kind}. Geser ⋮⋮ untuk mengurutkan atau memindahkan antarkolom.`),
            schema.areas[kind].map((area, i) => {
                const list = draft.layout[area] ??= [];
                return h('div', { class: 'column' },
                    h('div', { class: 'column-title' }, `Kolom ${i + 1}`),
                    sortList(list, kind, 0, (listId) => list.map((item, index) => areaCard(item, listId, index))),
                    h('button', { type: 'button', class: 'btn block', onclick: () => openComponentPicker(kind, (type) => {
                        const item = { section_type: type, is_enabled: true, configuration: newConfiguration(type) };
                        list.push(item);
                        expanded.add(item);
                        markDirty(true);
                    }) }, '+ Tambah komponen'));
            }),
        ];
    }

    // ---- Section beranda: section → kolom → komponen ----
    const presetLabel = (spans) => spans.join(' / ');
    const sectionSpans = (section) => section.columns.map((col) => col.col_span);

    function applyPreset(section, spans) {
        const dropped = section.columns.slice(spans.length).filter((col) => col.section_type);
        if (dropped.length && !confirm(`${dropped.length} kolom berisi komponen akan dihapus. Lanjutkan?`)) return;
        section.columns = spans.map((span, i) => ({ ...(section.columns[i] ?? { id: uid('col-'), section_type: null, configuration: {} }), col_span: span }));
        markDirty(true);
    }

    function columnCard(section, col, listId, index) {
        const meta = col.section_type ? componentMeta(col.section_type) : null;
        const details = col.section_type && expanded.has(col) ? componentDetails(col) : null;
        const pick = () => openComponentPicker('main', (type) => {
            col.section_type = type;
            col.configuration = newConfiguration(type);
            expanded.add(col);
            markDirty(true);
        });
        const spanSelect = h('select', {
            class: 'span-select', 'aria-label': `Lebar kolom ${index + 1}`, title: 'Lebar kolom (dari 12)',
            onchange: (e) => { col.col_span = Number(e.target.value); markDirty(true); },
        }, Array.from({ length: 12 }, (_, i) => i + 1).map((n) => h('option', { value: n, selected: n === col.col_span }, `${n}/12`)));

        return h('div', { class: 'card col-card', 'data-sort-item': true },
            h('div', { class: 'row' },
                handle(listId, index),
                h('span', { class: 'col-label' }, `Kolom ${index + 1}`),
                spanSelect,
                meta
                    ? h('span', { class: 'name', title: col.section_type }, meta.label, variantLabel(col))
                    : h('button', { type: 'button', class: 'btn pick', onclick: pick }, '+ Pilih komponen'),
                meta && componentDetails(col) ? h('button', { type: 'button', class: 'icon-btn', 'aria-label': 'Pengaturan', 'aria-expanded': String(expanded.has(col)), onclick: () => toggle(col) }, expanded.has(col) ? '▾' : '▸') : null,
                meta ? h('button', { type: 'button', class: 'icon-btn', 'aria-label': 'Ganti komponen', title: 'Ganti komponen', onclick: pick }, '⇄') : null,
                h('button', { type: 'button', class: 'icon-btn', 'aria-label': 'Hapus kolom', title: 'Hapus kolom', onclick: () => {
                    section.columns.splice(index, 1);
                    if (!section.columns.length) draft.sections.splice(draft.sections.indexOf(section), 1);
                    markDirty(true);
                } }, '✕')),
            details ? h('div', { class: 'details' }, details) : null);
    }

    function sectionCard(section, listId, index) {
        const spans = sectionSpans(section);
        const total = spans.reduce((sum, span) => sum + span, 0);
        const presetKey = Object.keys(schema.columnPresets).find((key) => presetLabel(schema.columnPresets[key]) === presetLabel(spans)) ?? '';
        return h('div', { class: 'card section-card' + (section.is_enabled ? '' : ' off'), 'data-sort-item': true },
            h('div', { class: 'row section-head' },
                handle(listId, index),
                h('input', {
                    type: 'checkbox', class: 'switch', checked: section.is_enabled, 'aria-label': `Tampilkan section ${index + 1}`,
                    onchange: (e) => { section.is_enabled = e.target.checked; markDirty(true); },
                }),
                h('span', { class: 'name' }, `Section ${index + 1}`,
                    h('small', { class: total === 12 ? '' : 'warn' }, `· ${section.columns.length} kolom${total === 12 ? '' : ` (total ${total}/12)`}`)),
                h('select', {
                    class: 'span-select', 'aria-label': 'Tata letak kolom', title: 'Tata letak kolom',
                    onchange: (e) => { if (e.target.value) applyPreset(section, schema.columnPresets[e.target.value]); },
                },
                    presetKey ? null : h('option', { value: '', selected: true }, 'Kustom'),
                    Object.entries(schema.columnPresets).map(([key, preset]) => h('option', { value: key, selected: key === presetKey }, presetLabel(preset)))),
                h('button', { type: 'button', class: 'icon-btn', 'aria-label': 'Hapus section', title: 'Hapus section', onclick: () => {
                    if (section.columns.some((col) => col.section_type) && !confirm(`Hapus section ${index + 1} beserta komponennya?`)) return;
                    draft.sections.splice(index, 1);
                    markDirty(true);
                } }, '✕')),
            h('div', { class: 'section-cols' },
                sortList(section.columns, `cols-${section.id}`, 0, (colListId) => section.columns.map((col, ci) => columnCard(section, col, colListId, ci))),
                section.columns.length < 4 ? h('button', { type: 'button', class: 'link-btn', onclick: () => {
                    // Kolom baru mengambil separuh lebar kolom terlebar.
                    const widest = section.columns.reduce((a, b) => (b.col_span > a.col_span ? b : a), section.columns[0]);
                    const half = Math.max(1, Math.floor(widest.col_span / 2));
                    widest.col_span -= half;
                    section.columns.push({ id: uid('col-'), col_span: half, section_type: null, configuration: {} });
                    markDirty(true);
                } }, '+ Tambah kolom') : null));
    }

    function sectionsPanel() {
        draft.sections ??= [];
        return [
            h('p', { class: 'hint' }, 'Susun beranda per section. Setiap section dibagi menjadi kolom (lebar dari 12), dan setiap kolom diisi satu komponen. Geser ⋮⋮ untuk mengurutkan section maupun kolom.'),
            sortList(draft.sections, 'sections', 0, (listId) => draft.sections.map((section, index) => sectionCard(section, listId, index))),
            h('button', { type: 'button', class: 'btn primary block', onclick: openLayoutPicker }, '+ Tambah Section'),
        ];
    }

    // ---- Modal pemilih ----
    function openModal(title, subtitle, content) {
        const close = () => modalHost.replaceChildren();
        const modal = h('div', { class: 'modal', role: 'dialog', 'aria-modal': 'true', 'aria-label': title },
            h('button', { type: 'button', class: 'icon-btn close-modal', 'aria-label': 'Tutup', onclick: close }, '✕'),
            h('h2', {}, title),
            subtitle ? h('p', { class: 'sub' }, subtitle) : null,
            content(close));
        const wrap = h('div', { class: 'modal-wrap', onclick: (e) => { if (e.target === wrap) close(); } }, modal);
        modalHost.replaceChildren(wrap);
        (modal.querySelector('[data-autofocus]') ?? modal.querySelector('button:not(.close-modal)'))?.focus();
    }

    function openLayoutPicker() {
        openModal('Tambah Section', 'Pilih tata letak kolom. Setiap kolom nantinya diisi satu komponen.', (close) =>
            h('div', { class: 'layout-grid' }, Object.entries(schema.columnPresets).map(([key, spans]) =>
                h('button', { type: 'button', class: 'layout-option', onclick: () => {
                    const section = { id: uid('sec-'), is_enabled: true, columns: spans.map((span) => ({ id: uid('col-'), col_span: span, section_type: null, configuration: {} })) };
                    draft.sections.push(section);
                    close();
                    markDirty(true);
                    if (spans.length === 1) openComponentPicker('main', (type) => {
                        Object.assign(section.columns[0], { section_type: type, configuration: newConfiguration(type) });
                        expanded.add(section.columns[0]);
                        markDirty(true);
                    });
                    root.querySelector('.section-card:last-of-type')?.scrollIntoView({ block: 'nearest' });
                } },
                    h('span', { class: 'layout-bars' }, spans.map((span) => h('span', { style: `flex: ${span}` }))),
                    h('span', {}, spans.length === 1 ? '1 kolom penuh' : `${spans.length} kolom · ${presetLabel(spans)}`)))));
    }

    function openComponentPicker(kind, onPick) {
        const types = schema.sections[kind] ?? [];
        openModal('Pilih Komponen', 'Komponen yang didaftarkan tema di theme.json → sections.' + kind + '.', (close) => {
            const list = h('div', { class: 'picker-list' });
            const fill = (query) => list.replaceChildren(...types
                .filter((type) => `${componentMeta(type).label} ${type}`.toLowerCase().includes(query.toLowerCase()))
                .map((type) => {
                    const meta = componentMeta(type);
                    const extras = [Object.keys(meta.variants ?? {}).length ? `${Object.keys(meta.variants).length} varian` : null, Object.keys(meta.settings ?? {}).length ? `${Object.keys(meta.settings).length} atribut` : null].filter(Boolean).join(' · ');
                    return h('button', { type: 'button', class: 'picker-item', onclick: () => { close(); onPick(type); } },
                        h('strong', {}, meta.label), h('span', {}, extras || type));
                }));
            fill('');
            return h('div', { class: 'picker' },
                h('input', { type: 'text', placeholder: 'Cari komponen...', 'aria-label': 'Cari komponen', 'data-autofocus': true, oninput: (e) => fill(e.target.value) }),
                list);
        });
    }

    // ---- Warna tema (theme.json → settings_schema) ----
    const COLOR_PRESETS = [
        ['Biru', '#2563eb', '#1e40af', '#0ea5e9'],
        ['Hijau', '#059669', '#0d9488', '#10b981'],
        ['Ungu', '#7c3aed', '#5b21b6', '#c026d3'],
        ['Merah', '#dc2626', '#991b1b', '#f97316'],
        ['Oranye', '#ea580c', '#9a3412', '#f59e0b'],
        ['Arang', '#334155', '#0f172a', '#64748b'],
    ];
    // primary_color → --theme-primary, sama seperti token di layout tema.
    const cssVar = (key) => `--theme-${key.replace(/_color$/, '')}`;

    function previewColors() {
        for (const [key, def] of Object.entries(schema.themeSettings ?? {})) {
            if (def.type === 'color' && draft.theme?.[key]) document.documentElement.style.setProperty(cssVar(key), draft.theme[key]);
        }
    }

    function colorsPanel() {
        draft.theme ??= {};
        const settings = schema.themeSettings ?? {};
        const colorKeys = ['primary_color', 'secondary_color', 'accent_color'].filter((key) => settings[key]?.type === 'color');
        const apply = (colors) => {
            colorKeys.forEach((key, i) => { draft.theme[key] = colors[i]; });
            previewColors();
            markDirty(true);
        };
        const defaults = colorKeys.map((key) => settings[key].default);
        const isCurrent = (colors) => colorKeys.every((key, i) => draft.theme[key]?.toLowerCase() === colors[i]?.toLowerCase());

        if (!Object.keys(settings).length) return [h('p', { class: 'hint' }, 'Tema ini tidak mendefinisikan settings_schema di theme.json.')];
        return [
            h('p', { class: 'hint' }, 'Warna tema dari theme.json → settings_schema. Perubahan langsung terlihat di halaman; klik Simpan agar tetap dipakai setelah reload.'),
            colorKeys.length === 3 ? [
                h('h3', {}, 'Palet cepat'),
                h('div', { class: 'palette-grid' },
                    [['Default tema', ...defaults], ...COLOR_PRESETS].map(([label, ...colors]) =>
                        h('button', { type: 'button', class: 'palette' + (isCurrent(colors) ? ' active' : ''), 'aria-pressed': String(isCurrent(colors)), onclick: () => apply(colors) },
                            h('span', { class: 'swatches' }, colors.map((color) => h('span', { style: `background: ${color}` }))),
                            label))),
            ] : null,
            h('h3', {}, 'Warna'),
            h('div', { class: 'details', style: 'border: 0; padding: 0' },
                fieldList(settings, draft.theme, (key, value) => { draft.theme[key] = value; previewColors(); markDirty(); })),
        ];
    }

    // ---- Pengaturan halaman ----
    function pagePanel() {
        const pages = Object.entries(schema.pages);
        const picker = h('label', { class: 'field' }, 'Grup pengaturan halaman',
            h('select', { onchange: (e) => { selectedPage = e.target.value || null; store.set('page', selectedPage); render(); } },
                h('option', { value: '' }, '— Pilih halaman —'),
                pages.map(([key, page]) => h('option', { value: key, selected: key === selectedPage }, page.label + (key === pageKey ? ' (aktif)' : '')))));

        const out = [];
        if (routeName === 'home' && !pageKey) {
            out.push(h('h3', {}, 'Beranda · Section'), ...sectionsPanel(), h('h3', {}, 'Pengaturan halaman lain'));
        } else if (!pageKey) {
            out.push(h('p', { class: 'hint' }, 'Halaman ini tidak memiliki grup pengaturan. Pilih grup lain di bawah.'));
        } else {
            out.push(h('p', { class: 'hint' }, `Pengaturan grup "${schema.pages[pageKey].label}" untuk halaman yang sedang dibuka.`));
        }
        out.push(picker);

        if (selectedPage && schema.pages[selectedPage]) {
            const values = draft.pages[selectedPage] ??= {};
            out.push(h('h3', {}, schema.pages[selectedPage].label),
                h('div', { class: 'details', style: 'border: 0; padding: 0' },
                    fieldList(schema.pages[selectedPage].fields, values, (key, value) => { values[key] = value; markDirty(); })));
        }
        return out;
    }

    // ---- Menu ----
    const URL_TYPES = ['custom_url', 'external_link'];

    function menuRow(item, listId, index, depth) {
        const typeLabel = schema.menuTypes[item.type] ?? schema.menuTypes.custom_url;
        return h('div', { class: 'card' + (item.is_visible ? '' : ' off'), 'data-sort-item': true },
            h('div', { class: 'row' },
                handle(listId, index),
                h('input', {
                    type: 'checkbox', class: 'switch', checked: item.is_visible, 'aria-label': `Tampilkan ${item.label}`,
                    onchange: (e) => { item.is_visible = e.target.checked; markDirty(true); },
                }),
                h('span', { class: 'name' }, item.label || '(tanpa label)',
                    h('span', { class: 'meta' }, `${typeLabel.split(' (')[0]} · ${item.url}${item.target === '_blank' ? ' ↗' : ''}`)),
                h('button', { type: 'button', class: 'icon-btn', 'aria-label': `Ubah ${item.label}`, title: 'Ubah', onclick: () => openMenuModal(item) }, '✎'),
                h('button', { type: 'button', class: 'icon-btn', 'aria-label': `Hapus ${item.label}`, title: 'Hapus', onclick: () => {
                    if (item.children?.length && !confirm(`Hapus "${item.label}" beserta ${item.children.length} submenunya?`)) return;
                    sortLists.get(listId).splice(index, 1);
                    markDirty(true);
                } }, '✕')),
            depth === 0 && item.children?.length ? menuList(item.children, 1, { class: 'menu-children' }) : null);
    }

    function menuList(list, depth, attrs = {}) {
        return sortList(list, 'menu', depth, (id) => list.map((item, index) => menuRow(item, id, index, depth)), attrs);
    }

    function menuPanel() {
        return [
            h('p', { class: 'hint' }, 'Menu utama header. Geser ⋮⋮ untuk mengurutkan atau memindahkan ke dalam submenu. Menu yang dimatikan tidak dikirim ke tema.'),
            h('button', { type: 'button', class: 'btn primary block', style: 'margin-bottom: 12px', onclick: () => openMenuModal(null) }, '+ Tambah Menu'),
            draft.menus.length ? menuList(draft.menus, 0) : h('div', { class: 'empty' }, 'Belum ada menu. Tema akan memakai menu bawaannya.'),
        ];
    }

    function findMenuParent(target) {
        if (draft.menus.includes(target)) return null;
        return draft.menus.find((menu) => menu.children?.includes(target)) ?? null;
    }

    function openMenuModal(item) {
        const editing = !!item;
        const currentParent = editing ? findMenuParent(item) : null;
        const form = {
            label: item?.label ?? '',
            parent: currentParent,
            type: item?.type ?? 'custom_url',
            reference: item?.reference ?? '',
            url: item?.url && URL_TYPES.includes(item?.type ?? 'custom_url') ? item.url : '',
            target: item?.target ?? '_self',
            is_visible: item?.is_visible ?? true,
        };
        let autoLabel = null; // label yang diisi otomatis dari referensi, boleh ditimpa lagi
        const errors = {};

        const close = () => { modalHost.replaceChildren(); };
        const sources = () => schema.menuSources[form.type] ?? [];
        const selectedSource = () => sources().find((source) => source.value === form.reference);

        function submit(e) {
            e.preventDefault();
            for (const key of Object.keys(errors)) delete errors[key];
            if (!form.label.trim()) errors.label = 'Label menu wajib diisi.';
            if (URL_TYPES.includes(form.type) && !form.url.trim()) errors.url = 'URL wajib diisi.';
            if (!URL_TYPES.includes(form.type) && !selectedSource()) errors.reference = 'Pilih salah satu data.';
            if (Object.keys(errors).length) { draw(); return; }

            const data = {
                label: form.label.trim(),
                type: form.type,
                reference: URL_TYPES.includes(form.type) ? null : form.reference,
                url: URL_TYPES.includes(form.type) ? form.url.trim() : selectedSource().url,
                target: form.target,
                is_visible: form.is_visible,
            };
            const target = editing ? Object.assign(item, data) : { ...data, children: [] };
            if (!editing || form.parent !== currentParent) {
                if (editing) {
                    const from = currentParent ? currentParent.children : draft.menus;
                    from.splice(from.indexOf(item), 1);
                }
                (form.parent ? (form.parent.children ??= []) : draft.menus).push(target);
            }
            close();
            markDirty(true);
        }

        function draw() {
            const isUrl = URL_TYPES.includes(form.type);
            const parentOptions = draft.menus.filter((menu) => menu !== item);
            const options = sources();
            const resolved = selectedSource();

            const modal = h('div', { class: 'modal', role: 'dialog', 'aria-modal': 'true', 'aria-labelledby': 'wb-menu-title' },
                h('button', { type: 'button', class: 'icon-btn close-modal', 'aria-label': 'Tutup', onclick: close }, '✕'),
                h('h2', { id: 'wb-menu-title' }, editing ? 'Ubah Menu Navigasi' : 'Tambah Menu Navigasi'),
                h('p', { class: 'sub' }, 'Konfigurasikan target URL, konten, atau referensi fitur untuk item navigasi ini.'),
                h('form', { onsubmit: submit, novalidate: true },
                    h('label', { class: 'field' }, 'Label Menu *',
                        h('input', { type: 'text', value: form.label, placeholder: 'Contoh: Beranda, Profil, Regulasi...', 'data-autofocus': true, oninput: (e) => { form.label = e.target.value; autoLabel = null; } }),
                        errors.label ? h('span', { class: 'error' }, errors.label) : null),
                    h('label', { class: 'field' }, 'Menu Induk (Parent / Sub-menu)',
                        h('select', {
                            disabled: !!item?.children?.length,
                            onchange: (e) => { form.parent = parentOptions[Number(e.target.value)] ?? null; },
                        },
                            h('option', { value: '' }, '-- Tidak Ada (Jadikan Menu Utama) --'),
                            parentOptions.map((menu, i) => h('option', { value: String(i), selected: menu === form.parent }, menu.label))),
                        item?.children?.length ? h('span', { class: 'field-help' }, 'Menu yang memiliki submenu tidak dapat dijadikan submenu.') : null),
                    h('label', { class: 'field' }, 'Tipe Menu *',
                        h('select', { onchange: (e) => {
                            form.type = e.target.value;
                            form.reference = '';
                            if (form.type === 'external_link') form.target = '_blank';
                            draw();
                        } },
                            Object.entries(schema.menuTypes).map(([key, text]) => h('option', { value: key, selected: key === form.type }, text)))),
                    isUrl
                        ? h('label', { class: 'field' }, 'URL / Anchor Target',
                            h('input', { type: 'text', value: form.url, placeholder: form.type === 'external_link' ? 'https://gorontaloprov.go.id' : '#fitur', oninput: (e) => { form.url = e.target.value; } }),
                            errors.url ? h('span', { class: 'error' }, errors.url) : null)
                        : h('label', { class: 'field' }, `Pilih ${schema.menuTypes[form.type].split(' (')[0]} *`,
                            options.length
                                ? h('select', { onchange: (e) => {
                                    form.reference = e.target.value;
                                    const source = selectedSource();
                                    // Isi label otomatis bila kosong atau masih hasil isian otomatis sebelumnya.
                                    if (source && (!form.label.trim() || form.label === autoLabel)) {
                                        autoLabel = source.label.replace(/^\[(BERITA|ARTIKEL)\]\s*/, '');
                                        form.label = autoLabel;
                                    }
                                    draw();
                                } },
                                    h('option', { value: '' }, '-- Pilih --'),
                                    options.map((source) => h('option', { value: source.value, selected: source.value === form.reference }, source.label)))
                                : h('span', { class: 'field-help' }, 'Belum ada data demo untuk tipe ini.'),
                            resolved ? h('span', { class: 'resolved' }, `URL: ${resolved.url}`) : null,
                            errors.reference ? h('span', { class: 'error' }, errors.reference) : null),
                    h('label', { class: 'field' }, 'Target Pembukaan',
                        h('select', { onchange: (e) => { form.target = e.target.value; } },
                            h('option', { value: '_self', selected: form.target === '_self' }, 'Tab Saat Ini (_self)'),
                            h('option', { value: '_blank', selected: form.target === '_blank' }, 'Tab Baru (_blank)'))),
                    h('label', { class: 'check' },
                        h('input', { type: 'checkbox', checked: form.is_visible, onchange: (e) => { form.is_visible = e.target.checked; } }),
                        'Tampilkan menu ini pada navigasi publik'),
                    h('div', { class: 'actions' },
                        h('button', { type: 'button', class: 'btn', onclick: close }, 'Batal'),
                        h('button', { type: 'submit', class: 'btn primary' }, editing ? 'Simpan Perubahan' : 'Tambah Menu'))));

            const wrap = h('div', { class: 'modal-wrap', onclick: (e) => { if (e.target === wrap) close(); } }, modal);
            const hadModal = modalHost.childElementCount > 0;
            modalHost.replaceChildren(wrap);
            if (!hadModal) modal.querySelector('[data-autofocus]').focus();
        }

        draw();
    }

    // ---- Kerangka drawer ----
    function render() {
        sortLists = new Map();
        const tabs = $('.tabs');
        tabs.replaceChildren(...TABS.map(([key, label]) => h('button', {
            type: 'button', role: 'tab', 'aria-selected': String(tab === key),
            onclick: () => { tab = key; store.set('tab', tab); render(); },
        }, label)));

        const scroll = body.scrollTop;
        const content = {
            page: pagePanel,
            colors: colorsPanel,
            components: sectionsPanel,
            header: () => columnsPanel('header'),
            footer: () => columnsPanel('footer'),
            menu: menuPanel,
        }[tab] ?? pagePanel;
        body.replaceChildren(...content().flat());
        body.scrollTop = scroll;
    }

    function setOpen(value) {
        open = value;
        store.set('open', open);
        drawer.hidden = backdrop.hidden = !open;
        if (open) { render(); drawer.querySelector('.close').focus(); }
    }

    async function send(method, data) {
        const response = await fetch(payload.endpoint, {
            method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': payload.csrfToken },
            body: data ? JSON.stringify(data) : undefined,
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
    }

    fab.addEventListener('click', () => setOpen(!open));
    backdrop.addEventListener('click', () => setOpen(false));
    $('.close').addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        if (modalHost.childElementCount) modalHost.replaceChildren();
        else if (open) setOpen(false);
    });

    $('.save').addEventListener('click', async (e) => {
        e.target.disabled = true;
        try { await send('POST', { state: draft }); dirty = false; location.reload(); }
        catch (error) { $('.status').textContent = 'Gagal menyimpan: ' + error.message; e.target.disabled = false; }
    });
    $('.reset').addEventListener('click', async () => {
        if (!confirm('Kembalikan semua pengaturan tema ini ke default?')) return;
        try { await send('DELETE'); dirty = false; location.reload(); }
        catch (error) { $('.status').textContent = 'Gagal reset: ' + error.message; }
    });
    window.addEventListener('beforeunload', (e) => { if (dirty) e.preventDefault(); });

    setOpen(open);
})();
</script>
