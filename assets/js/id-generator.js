(() => {
    'use strict';

    const employee = window.ID_EMPLOYEE;
    const assets = window.ID_TEMPLATE_ASSETS;
    const SVG_NS = 'http://www.w3.org/2000/svg';
    const CARD_WIDTH = 600;
    const CARD_HEIGHT = 960;
    const defaultPhotoPlacement = Object.freeze({
        x: 40,
        y: 280,
        size: 305,
        zoom: 1,
        panX: 0,
        panY: 0,
    });
    const photoPlacementStorageKey = `employee-id-photo-placement:${employee.id}`;

    const clamp = (value, minimum, maximum) => Math.min(Math.max(value, minimum), maximum);

    const normalizePhotoPlacement = value => {
        const size = clamp(Number(value?.size) || defaultPhotoPlacement.size, 180, 420);
        return {
            x: clamp(Number(value?.x) || 0, 0, CARD_WIDTH - size),
            y: clamp(Number(value?.y) || 0, 0, CARD_HEIGHT - size),
            size,
            zoom: clamp(Number(value?.zoom) || 1, 1, 2.5),
            panX: clamp(Number(value?.panX) || 0, -100, 100),
            panY: clamp(Number(value?.panY) || 0, -100, 100),
        };
    };

    const loadPhotoPlacement = () => {
        try {
            const stored = window.localStorage.getItem(photoPlacementStorageKey);
            return stored ? normalizePhotoPlacement(JSON.parse(stored)) : { ...defaultPhotoPlacement };
        } catch (_) {
            return { ...defaultPhotoPlacement };
        }
    };

    const photoPlacement = loadPhotoPlacement();

    const savePhotoPlacement = () => {
        try {
            window.localStorage.setItem(photoPlacementStorageKey, JSON.stringify(photoPlacement));
        } catch (_) {
            // Placement still works for the current page if browser storage is unavailable.
        }
    };

    const escapeXml = value => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&apos;');

    const fontSizeForLength = (text, normal, medium, small) => {
        const length = String(text || '').trim().length;
        if (length > 29) return small;
        if (length > 23) return medium;
        return normal;
    };

    const wrap = (text, maxCharacters, maxLines) => {
        const words = String(text || '').trim().split(/\s+/).filter(Boolean);
        const lines = [];
        let current = '';

        for (const word of words) {
            const candidate = current ? `${current} ${word}` : word;
            if (candidate.length <= maxCharacters || current === '' || lines.length === maxLines - 1) {
                current = candidate;
                continue;
            }

            lines.push(current);
            current = word;
        }

        if (current && lines.length < maxLines) {
            lines.push(current);
        }
        return lines;
    };

    const balancedLines = text => {
        const words = String(text || '').trim().split(/\s+/).filter(Boolean);
        if (words.length < 2 || words.join(' ').length <= 12) {
            return [words.join(' ')];
        }

        let best = [words.join(' ')];
        let bestDifference = Number.POSITIVE_INFINITY;
        for (let index = 1; index < words.length; index += 1) {
            const first = words.slice(0, index).join(' ');
            const second = words.slice(index).join(' ');
            const difference = Math.abs(first.length - second.length);
            if (difference < bestDifference) {
                best = [first, second];
                bestDifference = difference;
            }
        }
        return best;
    };

    const tspans = (lines, x, firstBaseline, lineHeight) => lines.map((line, index) =>
        `<tspan x="${x}" y="${firstBaseline + (index * lineHeight)}">${escapeXml(line)}</tspan>`
    ).join('');

    const fontDefinitions = `
        <style>
            @font-face {
                font-family: "MMC Office Medium";
                src: url("${assets.fontMedium}") format("truetype");
                font-weight: 500;
            }
            @font-face {
                font-family: "MMC Office Bold";
                src: url("${assets.fontBold}") format("truetype");
                font-weight: 700;
            }
            .mm-medium { font-family: "MMC Office Medium", sans-serif; font-weight: 500; }
            .mm-bold { font-family: "MMC Office Bold", sans-serif; font-weight: 700; }
        </style>`;

    const createPhotoMarkup = () => {
        const { x, y, size, zoom, panX, panY } = photoPlacement;
        const centerX = x + (size / 2);
        const centerY = y + (size / 2);
        const maximumShift = ((zoom - 1) * size) / 2;
        const shiftX = -(panX / 100) * maximumShift;
        const shiftY = -(panY / 100) * maximumShift;
        const matrixX = ((1 - zoom) * centerX) + shiftX;
        const matrixY = ((1 - zoom) * centerY) + shiftY;

        if (employee.photo) {
            return `<g id="photoEditable" data-photo-edit style="cursor:move;touch-action:none">
                        <g clip-path="url(#photoClip)">
                            <image id="employeePhoto" href="${escapeXml(employee.photo)}" x="${x}" y="${y}"
                                   width="${size}" height="${size}" preserveAspectRatio="xMidYMid slice"
                                   transform="matrix(${zoom} 0 0 ${zoom} ${matrixX} ${matrixY})"/>
                        </g>
                        <rect id="photoHitArea" x="${x}" y="${y}" width="${size}" height="${size}"
                              fill="transparent" stroke="none" pointer-events="all" data-photo-edit/>
                        <rect id="photoFrame" x="${x}" y="${y}" width="${size}" height="${size}"
                              fill="none" stroke="#111" stroke-width="2" pointer-events="none"/>
                    </g>`;
        }

        const insetSize = Math.max(0, size - 2);
        return `<g id="photoEditable" data-photo-edit style="cursor:move;touch-action:none">
                    <rect id="photoPlaceholderOuter" x="${x}" y="${y}" width="${size}" height="${size}"
                          fill="#fff" stroke="#111" stroke-width="2"/>
                    <rect id="photoPlaceholderInner" x="${x + 1}" y="${y + 1}"
                          width="${insetSize}" height="${insetSize}" fill="#e5e7eb"/>
                    <text id="photoPlaceholderText" x="${centerX}" y="${centerY}" text-anchor="middle"
                          class="mm-medium" fill="#94a3b8" font-size="24">PHOTO</text>
                    <rect id="photoHitArea" x="${x}" y="${y}" width="${size}" height="${size}"
                          fill="transparent" stroke="none" pointer-events="all" data-photo-edit/>
                </g>`;
    };

    const signatureMarkup = employee.signature
        ? `<image href="${escapeXml(employee.signature)}" x="227" y="733" width="151" height="91"
                  preserveAspectRatio="xMidYMid slice" clip-path="url(#signatureClip)"/>`
        : `<path d="M229 785c30-25 58 21 88-5 18-15 38-4 58-2"
                 fill="none" stroke="#111" stroke-width="2"/>`;

    const nameFontSize = fontSizeForLength(employee.name, 37.5, 33, 29);
    const positionFontSize = fontSizeForLength(employee.position, 33.3, 29, 25);
    const emergencyNameFontSize = fontSizeForLength(employee.emergencyName, 45.8, 41, 36);
    const departmentLines = balancedLines(employee.department);
    const longestDepartmentLine = Math.max(...departmentLines.map(line => line.length), 1);
    const isSalesDepartment = String(employee.department || '').trim().toLowerCase() === 'sales';
    const departmentFontSize = isSalesDepartment
        ? 92
        : longestDepartmentLine > 17
            ? 57
            : longestDepartmentLine > 13
                ? 68
                : longestDepartmentLine > 10
                    ? 77
                    : 86.5;
    const departmentTranslateX = isSalesDepartment ? 550 : 483.5;
    const emergencyAddressLines = wrap(employee.emergencyAddress, 23, 3);
    while (emergencyAddressLines.length < 3) {
        emergencyAddressLines.push('');
    }

    const createFront = () => `
    <svg xmlns="${SVG_NS}" id="idFront" viewBox="0 0 ${CARD_WIDTH} ${CARD_HEIGHT}"
         width="${CARD_WIDTH}" height="${CARD_HEIGHT}" role="img" aria-label="Employee ID front">
        <defs>
            ${fontDefinitions}
            <clipPath id="photoClip">
                <rect id="photoClipRect" x="${photoPlacement.x}" y="${photoPlacement.y}"
                      width="${photoPlacement.size}" height="${photoPlacement.size}"/>
            </clipPath>
            <clipPath id="signatureClip"><rect x="227" y="733" width="151" height="91"/></clipPath>
        </defs>
        <image href="${assets.frontTemplate}" x="0" y="0" width="${CARD_WIDTH}" height="${CARD_HEIGHT}"/>
        ${createPhotoMarkup()}
        <text x="381.25" y="535.85" class="mm-medium" fill="#000" font-size="28.5">
            <tspan x="381.25" y="535.85">EMPLOYEE NO.</tspan>
            <tspan x="381.25" y="564.05">${escapeXml(employee.employeeNo)}</tspan>
        </text>
        <text x="30.77" y="650.85" class="mm-medium" fill="#e50014"
              font-size="${nameFontSize}">${escapeXml(employee.name)}</text>
        <text x="33.1" y="688.78" class="mm-medium" fill="#000"
              font-size="${positionFontSize}">${escapeXml(employee.position)}</text>
        ${signatureMarkup}
        <text x="292.63" y="818.6" text-anchor="middle" class="mm-medium"
              fill="#000" font-size="25">Employee’s Signature</text>
        <text transform="translate(${departmentTranslateX} 454.7) rotate(-90)" class="mm-medium"
              fill="#fff" font-size="${departmentFontSize}">
            ${departmentLines.map((line, index) =>
                `<tspan x="0" y="${index * 89}">${escapeXml(line)}</tspan>`
            ).join('')}
        </text>
    </svg>`;

    const back = `
    <svg xmlns="${SVG_NS}" id="idBack" viewBox="0 0 ${CARD_WIDTH} ${CARD_HEIGHT}"
         width="${CARD_WIDTH}" height="${CARD_HEIGHT}" role="img" aria-label="Employee ID back">
        <defs>${fontDefinitions}</defs>
        <image href="${assets.backTemplate}" x="0" y="0" width="${CARD_WIDTH}" height="${CARD_HEIGHT}"/>
        <text x="301.15" y="299.61" text-anchor="middle" class="mm-medium"
              fill="#fff" font-size="${emergencyNameFontSize}">${escapeXml(employee.emergencyName)}</text>
        <text text-anchor="middle" class="mm-medium" fill="#fff" font-size="37">
            ${tspans(emergencyAddressLines, 299, 339.5, 33.333)}
        </text>
        <text x="299" y="410" text-anchor="middle" class="mm-medium"
              fill="#fff" font-size="38">${escapeXml(employee.emergencyNumber)}</text>
        <text x="74" y="640.5" class="mm-bold" fill="#000"
              font-size="25.083">${escapeXml(employee.dob)}</text>
        <text x="393" y="636.5" class="mm-bold" fill="#000"
              font-size="25.083">${escapeXml(employee.dateHired)}</text>
        <text x="550.38" text-anchor="end" class="mm-bold" fill="#000" font-size="25.083">
            ${tspans(
                [employee.sss, employee.philhealth, employee.tin, employee.hdmf],
                550.38,
                686.88,
                30.25
            )}
        </text>
    </svg>`;

    const renderFront = () => {
        document.getElementById('frontContainer').innerHTML = createFront();
    };

    renderFront();
    document.getElementById('backContainer').innerHTML = back;

    const frontContainer = document.getElementById('frontContainer');
    const photoInputs = [...document.querySelectorAll('[data-photo-control]')];
    const photoOutputs = [...document.querySelectorAll('[data-photo-output]')];

    const setSvgBox = (element, x, y, size) => {
        if (!element) return;
        element.setAttribute('x', String(x));
        element.setAttribute('y', String(y));
        element.setAttribute('width', String(size));
        element.setAttribute('height', String(size));
    };

    const syncPhotoControls = () => {
        for (const input of photoInputs) {
            const key = input.dataset.photoControl;
            if (key === 'x') input.max = String(CARD_WIDTH - photoPlacement.size);
            if (key === 'y') input.max = String(CARD_HEIGHT - photoPlacement.size);
            input.value = String(photoPlacement[key]);
        }

        for (const output of photoOutputs) {
            const key = output.dataset.photoOutput;
            const value = photoPlacement[key];
            output.value = key === 'zoom'
                ? `${value.toFixed(2)}×`
                : ['panX', 'panY'].includes(key)
                    ? `${Math.round(value)}%`
                    : `${Math.round(value)} px`;
        }
    };

    const applyPhotoPlacement = () => {
        const { x, y, size, zoom, panX, panY } = photoPlacement;
        const centerX = x + (size / 2);
        const centerY = y + (size / 2);
        const maximumShift = ((zoom - 1) * size) / 2;
        const shiftX = -(panX / 100) * maximumShift;
        const shiftY = -(panY / 100) * maximumShift;
        const matrixX = ((1 - zoom) * centerX) + shiftX;
        const matrixY = ((1 - zoom) * centerY) + shiftY;

        setSvgBox(document.getElementById('photoClipRect'), x, y, size);
        setSvgBox(document.getElementById('photoHitArea'), x, y, size);
        setSvgBox(document.getElementById('photoFrame'), x, y, size);

        const photo = document.getElementById('employeePhoto');
        if (photo) {
            setSvgBox(photo, x, y, size);
            photo.setAttribute('transform', `matrix(${zoom} 0 0 ${zoom} ${matrixX} ${matrixY})`);
        }

        const placeholderOuter = document.getElementById('photoPlaceholderOuter');
        const placeholderInner = document.getElementById('photoPlaceholderInner');
        const placeholderText = document.getElementById('photoPlaceholderText');
        setSvgBox(placeholderOuter, x, y, size);
        setSvgBox(placeholderInner, x + 1, y + 1, Math.max(0, size - 2));
        if (placeholderText) {
            placeholderText.setAttribute('x', String(centerX));
            placeholderText.setAttribute('y', String(centerY));
        }

        syncPhotoControls();
    };

    const updatePhotoPlacement = (changes, persist = true) => {
        Object.assign(photoPlacement, normalizePhotoPlacement({ ...photoPlacement, ...changes }));
        applyPhotoPlacement();
        if (persist) savePhotoPlacement();
    };

    photoInputs.forEach(input => input.addEventListener('input', () => {
        const key = input.dataset.photoControl;
        const value = Number(input.value);
        if (Number.isFinite(value)) {
            updatePhotoPlacement({ [key]: value });
        }
    }));

    document.querySelector('[data-photo-reset]')?.addEventListener('click', () => {
        Object.assign(photoPlacement, defaultPhotoPlacement);
        applyPhotoPlacement();
        savePhotoPlacement();
    });

    const cardPointFromPointer = event => {
        const svg = document.getElementById('idFront');
        const bounds = svg.getBoundingClientRect();
        return {
            x: ((event.clientX - bounds.left) / bounds.width) * CARD_WIDTH,
            y: ((event.clientY - bounds.top) / bounds.height) * CARD_HEIGHT,
        };
    };

    let photoDrag = null;
    frontContainer.addEventListener('pointerdown', event => {
        if (!(event.target instanceof Element) || !event.target.closest('[data-photo-edit]')) {
            return;
        }
        const point = cardPointFromPointer(event);
        photoDrag = {
            pointerId: event.pointerId,
            pointX: point.x,
            pointY: point.y,
            photoX: photoPlacement.x,
            photoY: photoPlacement.y,
        };
        frontContainer.classList.add('is-photo-dragging');
        event.preventDefault();
    });

    document.addEventListener('pointermove', event => {
        if (!photoDrag || event.pointerId !== photoDrag.pointerId) {
            return;
        }
        const point = cardPointFromPointer(event);
        updatePhotoPlacement({
            x: Math.round(photoDrag.photoX + point.x - photoDrag.pointX),
            y: Math.round(photoDrag.photoY + point.y - photoDrag.pointY),
        }, false);
        event.preventDefault();
    });

    const finishPhotoDrag = event => {
        if (!photoDrag || event.pointerId !== photoDrag.pointerId) {
            return;
        }
        photoDrag = null;
        frontContainer.classList.remove('is-photo-dragging');
        savePhotoPlacement();
    };

    document.addEventListener('pointerup', finishPhotoDrag);
    document.addEventListener('pointercancel', finishPhotoDrag);
    applyPhotoPlacement();

    async function logAction(side, mode) {
        try {
            await fetch('log_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    csrf_token: window.APP_CSRF,
                    employee_id: employee.id,
                    side,
                    mode,
                }),
            });
        } catch (_) {
            // The ID output should still work if audit logging is temporarily unavailable.
        }
    }

    function svgToPng(svgElement, filename) {
        const source = new XMLSerializer().serializeToString(svgElement);
        const blob = new Blob([source], { type: 'image/svg+xml;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const image = new Image();

        image.onload = () => {
            const canvas = document.createElement('canvas');
            canvas.width = CARD_WIDTH;
            canvas.height = CARD_HEIGHT;
            const context = canvas.getContext('2d');
            context.fillStyle = '#fff';
            context.fillRect(0, 0, CARD_WIDTH, CARD_HEIGHT);
            context.drawImage(image, 0, 0, CARD_WIDTH, CARD_HEIGHT);
            URL.revokeObjectURL(url);

            canvas.toBlob(png => {
                const downloadUrl = URL.createObjectURL(png);
                const anchor = document.createElement('a');
                anchor.href = downloadUrl;
                anchor.download = filename;
                anchor.click();
                setTimeout(() => URL.revokeObjectURL(downloadUrl), 1200);
            }, 'image/png');
        };
        image.src = url;
    }

    document.querySelectorAll('[data-download]').forEach(button => button.addEventListener('click', async () => {
        const side = button.dataset.download;
        const svg = document.getElementById(side === 'front' ? 'idFront' : 'idBack');
        svgToPng(svg, `${employee.employeeNo}_${side}.png`);
        await logAction(side, 'download');
    }));

    document.querySelectorAll('[data-print]').forEach(button => button.addEventListener('click', async () => {
        const side = button.dataset.print;
        const svg = document.getElementById(side === 'front' ? 'idFront' : 'idBack');
        const popup = window.open('', '_blank', 'width=760,height=1100');
        if (!popup) return;

        popup.document.write(`<!doctype html><html><head><title>${escapeXml(employee.employeeNo)} ${side}</title><style>@page{size:2.125in 3.375in;margin:0}html,body{margin:0;width:2.125in;height:3.375in}svg{display:block;width:2.125in;height:3.375in}</style></head><body>${svg.outerHTML}<script>window.onload=()=>window.print()<\/script></body></html>`);
        popup.document.close();
        await logAction(side, 'print');
    }));
})();
