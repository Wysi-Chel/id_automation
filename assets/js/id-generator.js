(() => {
    'use strict';

    const employee = window.ID_EMPLOYEE;
    const assets = window.ID_TEMPLATE_ASSETS;
    const SVG_NS = 'http://www.w3.org/2000/svg';
    const CARD_WIDTH = 600;
    const CARD_HEIGHT = 960;
    const templateStorageKey = `employee-id-template:${employee.id}`;
    const availableTemplateKeys = Object.keys(assets.templates || {});
    const fallbackTemplateKey = availableTemplateKeys.includes('general_santos')
        ? 'general_santos'
        : availableTemplateKeys[0];
    const defaultTemplateKey = availableTemplateKeys.includes(employee.templateKey)
        ? employee.templateKey
        : fallbackTemplateKey;
    const defaultPhotoPlacements = Object.freeze({
        mitsubishi: Object.freeze({
            x: 40,
            y: 280,
            size: 305,
            zoom: 1,
            panX: 0,
            panY: 0,
        }),
        fuso_reference_v2: Object.freeze({
            x: 40,
            y: 75,
            size: 560,
            zoom: 1,
            panX: 0,
            panY: 0,
        }),
        ntr: Object.freeze({
            x: 114,
            y: 294,
            size: 276,
            zoom: 1,
            panX: 0,
            panY: 0,
        }),
    });
    const legacyPhotoPlacementStorageKey = `employee-id-photo-placement:${employee.id}`;

    const clamp = (value, minimum, maximum) => Math.min(Math.max(value, minimum), maximum);
    const loadTemplateKey = () => {
        if (availableTemplateKeys.includes(employee.templateKey)) {
            return employee.templateKey;
        }
        try {
            const stored = window.localStorage.getItem(templateStorageKey);
            return availableTemplateKeys.includes(stored) ? stored : defaultTemplateKey;
        } catch (_) {
            return defaultTemplateKey;
        }
    };
    let selectedTemplateKey = loadTemplateKey();
    const selectedTemplate = () => assets.templates[selectedTemplateKey] || assets.templates[defaultTemplateKey];
    const isFusoTemplate = () => selectedTemplate().layout === 'fuso';
    const isNtrTemplate = () => selectedTemplate().layout === 'ntr';
    const photoPlacementProfile = () => isFusoTemplate()
        ? 'fuso_reference_v2'
        : isNtrTemplate()
            ? 'ntr'
            : 'mitsubishi';
    const defaultPhotoPlacement = () => defaultPhotoPlacements[photoPlacementProfile()];
    const photoPlacementStorageKey = () =>
        `employee-id-photo-placement:${employee.id}:${photoPlacementProfile()}`;

    const normalizePhotoPlacement = value => {
        const defaults = defaultPhotoPlacement();
        const size = clamp(Number(value?.size) || defaults.size, 180, 560);
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
            let stored = window.localStorage.getItem(photoPlacementStorageKey());
            if (!stored && photoPlacementProfile() === 'mitsubishi') {
                stored = window.localStorage.getItem(legacyPhotoPlacementStorageKey);
            }
            return stored ? normalizePhotoPlacement(JSON.parse(stored)) : { ...defaultPhotoPlacement() };
        } catch (_) {
            return { ...defaultPhotoPlacement() };
        }
    };

    const photoPlacement = loadPhotoPlacement();

    const savePhotoPlacement = () => {
        try {
            window.localStorage.setItem(photoPlacementStorageKey(), JSON.stringify(photoPlacement));
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
                font-family: "MMC Office Regular";
                src: url("${assets.fontRegular}") format("truetype");
                font-weight: 400;
            }
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
            @font-face {
                font-family: "Hyundai Sans Head Office";
                src: url("${assets.hyundaiRegular}") format("truetype");
                font-weight: 400;
            }
            @font-face {
                font-family: "Hyundai Sans Head Office";
                src: url("${assets.hyundaiBold}") format("truetype");
                font-weight: 700;
            }
            @font-face {
                font-family: "Hyundai Sans Text Office";
                src: url("${assets.hyundaiTextMedium}") format("truetype");
                font-weight: 500;
            }
            @font-face {
                font-family: "Lucida Sans Unicode Embedded";
                src: url("${assets.lucidaSansUnicode}") format("truetype");
                font-weight: 400;
            }
            .mm-regular { font-family: "MMC Office Regular", sans-serif; font-weight: 400; }
            .mm-medium { font-family: "MMC Office Medium", sans-serif; font-weight: 500; }
            .mm-bold { font-family: "MMC Office Bold", sans-serif; font-weight: 700; }
            .hyundai-regular { font-family: "Hyundai Sans Head Office", sans-serif; font-weight: 400; }
            .hyundai-bold { font-family: "Hyundai Sans Head Office", sans-serif; font-weight: 700; }
            .hyundai-text-medium { font-family: "Hyundai Sans Text Office", sans-serif; font-weight: 500; }
            .lucida { font-family: "Lucida Sans Unicode Embedded", "Lucida Sans Unicode", sans-serif; font-weight: 400; }
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
            const photoAspectRatio = isFusoTemplate() ? 'xMidYMax meet' : 'xMidYMid slice';
            const frame = isFusoTemplate() || isNtrTemplate()
                ? ''
                : `<rect id="photoFrame" x="${x}" y="${y}" width="${size}" height="${size}"
                              fill="none" stroke="#111" stroke-width="2" pointer-events="none"/>`;
            return `<g id="photoEditable" data-photo-edit style="cursor:move;touch-action:none">
                        <g clip-path="url(#photoClip)">
                            <image id="employeePhoto" href="${escapeXml(employee.photo)}" x="${x}" y="${y}"
                                   width="${size}" height="${size}" preserveAspectRatio="${photoAspectRatio}"
                                   transform="matrix(${zoom} 0 0 ${zoom} ${matrixX} ${matrixY})"/>
                        </g>
                        <rect id="photoHitArea" x="${x}" y="${y}" width="${size}" height="${size}"
                              fill="transparent" stroke="none" pointer-events="all" data-photo-edit/>
                        ${frame}
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

    const mitsubishiSignatureMarkup = employee.signature
        ? `<image href="${escapeXml(employee.signature)}" x="227" y="733" width="151" height="91"
                  preserveAspectRatio="xMidYMid slice" clip-path="url(#signatureClip)"/>`
        : `<path d="M229 785c30-25 58 21 88-5 18-15 38-4 58-2"
                 fill="none" stroke="#111" stroke-width="2"/>`;

    const fusoSignatureMarkup = employee.signature
        ? `<image href="${escapeXml(employee.signature)}" x="248" y="420" width="165" height="145"
                  preserveAspectRatio="xMidYMid slice"/>`
        : `<path d="M255 510c36-42 63 31 95-8 19-23 38-5 58-1"
                 fill="none" stroke="#111" stroke-width="2"/>`;

    const ntrSignatureMarkup = employee.signature
        ? `<image href="${escapeXml(employee.signature)}" x="230" y="795" width="220" height="85"
                  preserveAspectRatio="xMidYMid slice" style="filter:invert(1)"/>`
        : `<path d="M245 845c38-38 72 27 106-8 20-20 46-4 77-1"
                 fill="none" stroke="#fff" stroke-width="2"/>`;

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
    const fusoDepartmentText = `${String(employee.department || '').trim()} Department`.trim();
    const fusoDepartmentFontSize = fontSizeForLength(fusoDepartmentText, 54.3, 47, 40);
    const fusoNameFontSize = fontSizeForLength(employee.name, 39, 34, 29);
    const fusoPositionFontSize = fontSizeForLength(employee.position, 32.4, 28, 24);
    const fusoEmergencyNameFontSize = fontSizeForLength(employee.emergencyName, 32, 28, 24);
    const fusoEmergencyAddressLines = [String(employee.emergencyAddress || '').trim()];
    const fusoEmergencyAddressFontSize = fusoEmergencyAddressLines[0].length > 58
        ? 14.5
        : fusoEmergencyAddressLines[0].length > 48
            ? 16.5
            : 18.7;
    const fusoDateFontSize = value => String(value || '').length > 13 ? 18 : 23;
    const ntrDepartmentText = `${String(employee.department || '').trim()} Department`.trim();
    const ntrDepartmentFontSize = fontSizeForLength(ntrDepartmentText, 60, 52, 44);
    const ntrNameFontSize = fontSizeForLength(employee.name, 35.4, 31, 27);
    const ntrPositionFontSize = fontSizeForLength(employee.position, 33, 29, 25);
    const ntrEmergencyNameFontSize = fontSizeForLength(employee.emergencyName, 36.8, 32, 28);
    const ntrEmergencyAddressLines = wrap(employee.emergencyAddress, 32, 2);
    const ntrDateOfBirth = String(employee.dob || '—').replaceAll('/', '-');
    const ntrDateHired = String(employee.dateHired || '—').replaceAll('/', '-');
    while (ntrEmergencyAddressLines.length < 2) {
        ntrEmergencyAddressLines.push('');
    }

    const backTextDefinitions = () => {
        if (isNtrTemplate()) {
            return {
                dateOfBirth: { label: 'Date of birth', x: 561, y: 423, fontSize: 24.2 },
                dateHired: { label: 'Date hired', x: 561, y: 465, fontSize: 24.2 },
                governmentNumbers: { label: 'Government numbers', x: 561, y: 512.5, fontSize: 24.2 },
                emergencyName: { label: 'Emergency contact name', x: 336.5, y: 694, fontSize: ntrEmergencyNameFontSize },
                emergencyAddress: { label: 'Emergency address', x: 331, y: 728, fontSize: 22 },
                emergencyNumber: { label: 'Emergency phone number', x: 331, y: 781, fontSize: 24.5 },
            };
        }

        if (isFusoTemplate()) {
            return {
                emergencyName: { label: 'Emergency contact name', x: 301.5, y: 271.5, fontSize: fusoEmergencyNameFontSize },
                emergencyAddress: { label: 'Emergency address', x: 301.5, y: 303, fontSize: fusoEmergencyAddressFontSize },
                emergencyNumber: { label: 'Emergency phone number', x: 301.5, y: 324, fontSize: 26 },
                employeeName: { label: 'Employee name', x: 309.3, y: 574.5, fontSize: fontSizeForLength(employee.name, 36, 31, 27) },
                dateOfBirth: { label: 'Date of birth', x: 83.2, y: 652, fontSize: fusoDateFontSize(employee.dob) },
                dateHired: { label: 'Date hired', x: 400.3, y: 652, fontSize: fusoDateFontSize(employee.dateHired) },
                governmentNumbers: { label: 'Government numbers', x: 533.4, y: 705.6, fontSize: 24.2 },
            };
        }

        const definitions = {
            emergencyName: { label: 'Emergency contact name', x: 301.15, y: 299.61, fontSize: emergencyNameFontSize },
            emergencyAddress: { label: 'Emergency address', x: 299, y: 339.5, fontSize: 37 },
            emergencyNumber: { label: 'Emergency phone number', x: 299, y: 410, fontSize: 38 },
            dateOfBirth: { label: 'Date of birth', x: 74, y: 640.5, fontSize: 25.083 },
            dateHired: { label: 'Date hired', x: 393, y: 636.5, fontSize: 25.083 },
            governmentNumbers: { label: 'Government numbers', x: 550.38, y: 686.88, fontSize: 25.083 },
        };
        if (Array.isArray(selectedTemplate().companyAddress)) {
            definitions.companyAddressLine1 = { label: 'Company address – first line', x: 300, y: 522, fontSize: 24.166 };
            definitions.companyAddressLine2 = { label: 'Company address – second line', x: 300, y: 552, fontSize: 24.166 };
        }
        return definitions;
    };

    const normalizeBackTextSetting = (value, defaults) => {
        const numberOrDefault = (candidate, fallback) => {
            const number = Number(candidate);
            return Number.isFinite(number) ? number : fallback;
        };
        return {
            x: clamp(numberOrDefault(value?.x, defaults.x), 0, CARD_WIDTH),
            y: clamp(numberOrDefault(value?.y, defaults.y), 0, CARD_HEIGHT),
            fontSize: clamp(numberOrDefault(value?.fontSize, defaults.fontSize), 8, 120),
        };
    };

    const backTextStorageKey = () => `employee-id-back-text:${employee.id}:${selectedTemplateKey}`;
    const loadBackTextSettings = () => {
        const definitions = backTextDefinitions();
        let stored = {};
        try {
            stored = JSON.parse(window.localStorage.getItem(backTextStorageKey()) || '{}');
        } catch (_) {
            stored = {};
        }

        return Object.fromEntries(Object.entries(definitions).map(([key, defaults]) => [
            key,
            normalizeBackTextSetting(stored[key], defaults),
        ]));
    };

    let backTextSettings = loadBackTextSettings();
    let selectedBackTextKey = Object.keys(backTextSettings)[0];
    const backTextSetting = key => backTextSettings[key] || backTextDefinitions()[key];
    const backTextAttributes = key => `data-back-text-key="${key}" style="cursor:move;touch-action:none"`;

    const saveBackTextSettings = () => {
        try {
            window.localStorage.setItem(backTextStorageKey(), JSON.stringify(backTextSettings));
        } catch (_) {
            // Text editing still works for the current page if browser storage is unavailable.
        }
    };

    const frontTextDefinitions = () => {
        if (isNtrTemplate()) {
            return {
                department: { label: 'Department (vertical)', x: 70, y: 901, fontSize: ntrDepartmentFontSize, rotated: true },
                employeeNo: { label: 'Employee number', x: 499, y: 558.5, fontSize: 26.8 },
                name: { label: 'Employee name', x: 341, y: 638, fontSize: ntrNameFontSize },
                position: { label: 'Position', x: 337, y: 672, fontSize: ntrPositionFontSize },
                signatureLabel: { label: 'Signature caption', x: 340, y: 884, fontSize: 24.7 },
            };
        }

        if (isFusoTemplate()) {
            return {
                department: { label: 'Department (vertical)', x: 53.4, y: 566.9, fontSize: fusoDepartmentFontSize, rotated: true },
                name: { label: 'Employee name', x: 30.3, y: 732.6, fontSize: fusoNameFontSize },
                position: { label: 'Position', x: 30.3, y: 768.7, fontSize: fusoPositionFontSize },
                idNumber: { label: 'ID number (vertical)', x: 557.3, y: 906.5, fontSize: 25.6, rotated: true },
            };
        }

        return {
            department: { label: 'Department (vertical)', x: departmentTranslateX, y: 454.7, fontSize: departmentFontSize, rotated: true },
            employeeNo: { label: 'Employee number block', x: 381.25, y: 535.85, fontSize: 28.5 },
            name: { label: 'Employee name', x: 30.77, y: 650.85, fontSize: nameFontSize },
            position: { label: 'Position', x: 33.1, y: 688.78, fontSize: positionFontSize },
            signatureLabel: { label: 'Signature caption', x: 292.63, y: 818.6, fontSize: 25 },
        };
    };

    const normalizeFrontTextSetting = (value, defaults) => {
        const numberOrDefault = (candidate, fallback) => {
            const number = Number(candidate);
            return Number.isFinite(number) ? number : fallback;
        };
        return {
            x: clamp(numberOrDefault(value?.x, defaults.x), 0, CARD_WIDTH),
            y: clamp(numberOrDefault(value?.y, defaults.y), 0, CARD_HEIGHT),
            fontSize: clamp(numberOrDefault(value?.fontSize, defaults.fontSize), 8, 120),
        };
    };

    const frontTextStorageKey = () => `employee-id-front-text:${employee.id}:${selectedTemplateKey}`;
    const loadFrontTextSettings = () => {
        const definitions = frontTextDefinitions();
        let stored = {};
        try {
            stored = JSON.parse(window.localStorage.getItem(frontTextStorageKey()) || '{}');
        } catch (_) {
            stored = {};
        }

        return Object.fromEntries(Object.entries(definitions).map(([key, defaults]) => [
            key,
            normalizeFrontTextSetting(stored[key], defaults),
        ]));
    };

    let frontTextSettings = loadFrontTextSettings();
    let selectedFrontTextKey = Object.keys(frontTextSettings)[0];
    const frontTextSetting = key => frontTextSettings[key] || frontTextDefinitions()[key];

    // The vertical blocks (department strip, FUSO ID number) carry their position in a
    // translate rather than x/y, so the placement attributes differ per element.
    const frontTextAttributes = key => {
        const setting = frontTextSetting(key);
        const placement = frontTextDefinitions()[key]?.rotated
            ? `transform="translate(${setting.x} ${setting.y}) rotate(-90)"`
            : `x="${setting.x}" y="${setting.y}"`;
        return `${placement} font-size="${setting.fontSize}" data-front-text-key="${key}" style="cursor:move;touch-action:none"`;
    };

    const saveFrontTextSettings = () => {
        try {
            window.localStorage.setItem(frontTextStorageKey(), JSON.stringify(frontTextSettings));
        } catch (_) {
            // Text editing still works for the current page if browser storage is unavailable.
        }
    };

    const createMitsubishiFront = () => `
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
        <image href="${selectedTemplate().front}" x="0" y="0" width="${CARD_WIDTH}" height="${CARD_HEIGHT}"/>
        ${createPhotoMarkup()}
        <text class="mm-medium" fill="#000" ${frontTextAttributes('employeeNo')}>
            <tspan x="${frontTextSetting('employeeNo').x}" y="${frontTextSetting('employeeNo').y}">EMPLOYEE NO.</tspan>
            <tspan x="${frontTextSetting('employeeNo').x}" y="${frontTextSetting('employeeNo').y + 28.2}">${escapeXml(employee.employeeNo)}</tspan>
        </text>
        <text class="mm-medium" fill="#e50014"
              ${frontTextAttributes('name')}>${escapeXml(employee.name)}</text>
        <text class="mm-medium" fill="#000"
              ${frontTextAttributes('position')}>${escapeXml(employee.position)}</text>
        ${mitsubishiSignatureMarkup}
        <text text-anchor="middle" class="mm-medium"
              fill="#000" ${frontTextAttributes('signatureLabel')}>Employee’s Signature</text>
        <text class="mm-medium" fill="#fff" ${frontTextAttributes('department')}>
            ${departmentLines.map((line, index) =>
                `<tspan x="0" y="${index * 89 * (frontTextSetting('department').fontSize / departmentFontSize)}">${escapeXml(line)}</tspan>`
            ).join('')}
        </text>
    </svg>`;

    const createFusoFront = () => `
    <svg xmlns="${SVG_NS}" id="idFront" viewBox="0 0 ${CARD_WIDTH} ${CARD_HEIGHT}"
         width="${CARD_WIDTH}" height="${CARD_HEIGHT}" role="img" aria-label="FUSO employee ID front">
        <defs>
            ${fontDefinitions}
            <clipPath id="photoClip">
                <rect id="photoClipRect" x="${photoPlacement.x}" y="${photoPlacement.y}"
                      width="${photoPlacement.size}" height="${photoPlacement.size}"/>
            </clipPath>
        </defs>
        <image href="${selectedTemplate().front}" x="0" y="0" width="${CARD_WIDTH}" height="${CARD_HEIGHT}"/>
        ${createPhotoMarkup()}
        ${selectedTemplate().frontOverlay
            ? `<image href="${escapeXml(selectedTemplate().frontOverlay)}" x="0" y="0"
                      width="${CARD_WIDTH}" height="${CARD_HEIGHT}" pointer-events="none"/>`
            : ''}
        <text class="mm-medium"
              fill="#f7f7f7" ${frontTextAttributes('department')}>${escapeXml(fusoDepartmentText)}</text>
        <text class="mm-bold" fill="#000"
              ${frontTextAttributes('name')}>${escapeXml(employee.name)}</text>
        <text class="mm-regular" fill="#000"
              ${frontTextAttributes('position')}>${escapeXml(employee.position)}</text>
        <text class="mm-medium"
              fill="#fff" ${frontTextAttributes('idNumber')}>ID No. ${escapeXml(employee.employeeNo)}</text>
    </svg>`;

    const createNtrFront = () => `
    <svg xmlns="${SVG_NS}" id="idFront" viewBox="0 0 ${CARD_WIDTH} ${CARD_HEIGHT}"
         width="${CARD_WIDTH}" height="${CARD_HEIGHT}" role="img" aria-label="NTRprising employee ID front">
        <defs>
            ${fontDefinitions}
            <clipPath id="photoClip">
                <rect id="photoClipRect" x="${photoPlacement.x}" y="${photoPlacement.y}"
                      width="${photoPlacement.size}" height="${photoPlacement.size}" rx="7" ry="7"/>
            </clipPath>
        </defs>
        <image href="${selectedTemplate().front}" x="0" y="0" width="${CARD_WIDTH}" height="${CARD_HEIGHT}"/>
        ${createPhotoMarkup()}
        <text class="hyundai-bold"
              fill="#fff" ${frontTextAttributes('department')}>${escapeXml(ntrDepartmentText)}</text>
        <text text-anchor="middle" class="hyundai-text-medium"
              fill="#fff" ${frontTextAttributes('employeeNo')}>${escapeXml(employee.employeeNo)}</text>
        <text text-anchor="middle" class="lucida"
              fill="#fff" ${frontTextAttributes('name')}>${escapeXml(employee.name)}</text>
        <text text-anchor="middle" class="hyundai-regular"
              fill="#fff" ${frontTextAttributes('position')}>${escapeXml(employee.position)}</text>
        ${ntrSignatureMarkup}
        <text text-anchor="middle" class="hyundai-regular"
              fill="#fff" ${frontTextAttributes('signatureLabel')}>Employee’s Signature</text>
    </svg>`;

    const createFront = () => isNtrTemplate()
        ? createNtrFront()
        : isFusoTemplate()
            ? createFusoFront()
            : createMitsubishiFront();

    const companyAddressMarkup = () => {
        const address = selectedTemplate().companyAddress;
        if (!Array.isArray(address) || address.length < 2) {
            return '';
        }

        const firstLine = backTextSetting('companyAddressLine1');
        const secondLine = backTextSetting('companyAddressLine2');

        return `
        <text x="${firstLine.x}" y="${firstLine.y}" text-anchor="middle" fill="#000"
              font-size="${firstLine.fontSize}" ${backTextAttributes('companyAddressLine1')}>
            <tspan class="mm-medium">COMPANY ADDRESS: </tspan>
            <tspan class="mm-bold">${escapeXml(address[0])}</tspan>
        </text>
        <text x="${secondLine.x}" y="${secondLine.y}" text-anchor="middle" class="mm-bold"
              fill="#000" font-size="${secondLine.fontSize}" ${backTextAttributes('companyAddressLine2')}>${escapeXml(address[1])}</text>`;
    };

    const createMitsubishiBack = () => `
    <svg xmlns="${SVG_NS}" id="idBack" viewBox="0 0 ${CARD_WIDTH} ${CARD_HEIGHT}"
         width="${CARD_WIDTH}" height="${CARD_HEIGHT}" role="img" aria-label="Employee ID back">
        <defs>${fontDefinitions}</defs>
        <image href="${selectedTemplate().back}" x="0" y="0" width="${CARD_WIDTH}" height="${CARD_HEIGHT}"/>
        <text x="${backTextSetting('emergencyName').x}" y="${backTextSetting('emergencyName').y}"
              text-anchor="middle" class="mm-medium" fill="#fff"
              font-size="${backTextSetting('emergencyName').fontSize}" ${backTextAttributes('emergencyName')}>${escapeXml(employee.emergencyName)}</text>
        <text text-anchor="middle" class="mm-medium" fill="#fff"
              font-size="${backTextSetting('emergencyAddress').fontSize}" ${backTextAttributes('emergencyAddress')}>
            ${tspans(emergencyAddressLines, backTextSetting('emergencyAddress').x, backTextSetting('emergencyAddress').y, 33.333)}
        </text>
        <text x="${backTextSetting('emergencyNumber').x}" y="${backTextSetting('emergencyNumber').y}"
              text-anchor="middle" class="mm-medium" fill="#fff"
              font-size="${backTextSetting('emergencyNumber').fontSize}" ${backTextAttributes('emergencyNumber')}>${escapeXml(employee.emergencyNumber)}</text>
        ${companyAddressMarkup()}
        <text x="${backTextSetting('dateOfBirth').x}" y="${backTextSetting('dateOfBirth').y}"
              class="mm-bold" fill="#000" font-size="${backTextSetting('dateOfBirth').fontSize}"
              ${backTextAttributes('dateOfBirth')}>${escapeXml(employee.dob)}</text>
        <text x="${backTextSetting('dateHired').x}" y="${backTextSetting('dateHired').y}"
              class="mm-bold" fill="#000" font-size="${backTextSetting('dateHired').fontSize}"
              ${backTextAttributes('dateHired')}>${escapeXml(employee.dateHired)}</text>
        <text x="${backTextSetting('governmentNumbers').x}" text-anchor="end" class="mm-bold" fill="#000"
              font-size="${backTextSetting('governmentNumbers').fontSize}" ${backTextAttributes('governmentNumbers')}>
            ${tspans(
                [employee.sss, employee.philhealth, employee.tin, employee.hdmf],
                backTextSetting('governmentNumbers').x,
                backTextSetting('governmentNumbers').y,
                30.25
            )}
        </text>
    </svg>`;

    const createFusoBack = () => {
        return `
    <svg xmlns="${SVG_NS}" id="idBack" viewBox="0 0 ${CARD_WIDTH} ${CARD_HEIGHT}"
         width="${CARD_WIDTH}" height="${CARD_HEIGHT}" role="img" aria-label="FUSO employee ID back">
        <defs>${fontDefinitions}</defs>
        <image href="${selectedTemplate().back}" x="0" y="0" width="${CARD_WIDTH}" height="${CARD_HEIGHT}"/>
        <text x="${backTextSetting('emergencyName').x}" y="${backTextSetting('emergencyName').y}"
              text-anchor="middle" class="mm-medium" fill="#000"
              font-size="${backTextSetting('emergencyName').fontSize}" ${backTextAttributes('emergencyName')}>${escapeXml(employee.emergencyName)}</text>
        <text text-anchor="middle" class="mm-medium" fill="#000"
              font-size="${backTextSetting('emergencyAddress').fontSize}" ${backTextAttributes('emergencyAddress')}>
            ${tspans(fusoEmergencyAddressLines, backTextSetting('emergencyAddress').x, backTextSetting('emergencyAddress').y, 21)}
        </text>
        <text x="${backTextSetting('emergencyNumber').x}" y="${backTextSetting('emergencyNumber').y}"
              text-anchor="middle" class="mm-medium" fill="#000"
              font-size="${backTextSetting('emergencyNumber').fontSize}" ${backTextAttributes('emergencyNumber')}>${escapeXml(employee.emergencyNumber)}</text>
        ${fusoSignatureMarkup}
        <text x="${backTextSetting('employeeName').x}" y="${backTextSetting('employeeName').y}"
              text-anchor="middle" class="mm-medium" fill="#000"
              font-size="${backTextSetting('employeeName').fontSize}" ${backTextAttributes('employeeName')}>${escapeXml(employee.name)}</text>
        <text x="${backTextSetting('dateOfBirth').x}" y="${backTextSetting('dateOfBirth').y}"
              class="mm-bold" fill="#000" font-size="${backTextSetting('dateOfBirth').fontSize}"
              ${backTextAttributes('dateOfBirth')}>${escapeXml(employee.dob)}</text>
        <text x="${backTextSetting('dateHired').x}" y="${backTextSetting('dateHired').y}"
              class="mm-bold" fill="#000" font-size="${backTextSetting('dateHired').fontSize}"
              ${backTextAttributes('dateHired')}>${escapeXml(employee.dateHired)}</text>
        <text x="${backTextSetting('governmentNumbers').x}" text-anchor="end" class="mm-bold" fill="#000"
              font-size="${backTextSetting('governmentNumbers').fontSize}" ${backTextAttributes('governmentNumbers')}>
            ${tspans(
                [employee.sss, employee.philhealth, employee.tin, employee.hdmf],
                backTextSetting('governmentNumbers').x,
                backTextSetting('governmentNumbers').y,
                29.2
            )}
        </text>
    </svg>`;
    };

    const createNtrBack = () => `
    <svg xmlns="${SVG_NS}" id="idBack" viewBox="0 0 ${CARD_WIDTH} ${CARD_HEIGHT}"
         width="${CARD_WIDTH}" height="${CARD_HEIGHT}" role="img" aria-label="NTRprising employee ID back">
        <defs>${fontDefinitions}</defs>
        <image href="${selectedTemplate().back}" x="0" y="0" width="${CARD_WIDTH}" height="${CARD_HEIGHT}"/>
        <text x="${backTextSetting('dateOfBirth').x}" y="${backTextSetting('dateOfBirth').y}"
              text-anchor="end" class="hyundai-bold" fill="#000"
              font-size="${backTextSetting('dateOfBirth').fontSize}" ${backTextAttributes('dateOfBirth')}>${escapeXml(ntrDateOfBirth)}</text>
        <text x="${backTextSetting('dateHired').x}" y="${backTextSetting('dateHired').y}"
              text-anchor="end" class="hyundai-bold" fill="#000"
              font-size="${backTextSetting('dateHired').fontSize}" ${backTextAttributes('dateHired')}>${escapeXml(ntrDateHired)}</text>
        <text x="${backTextSetting('governmentNumbers').x}" text-anchor="end" class="mm-bold" fill="#000"
              font-size="${backTextSetting('governmentNumbers').fontSize}" ${backTextAttributes('governmentNumbers')}>
            ${tspans([employee.sss, employee.philhealth, employee.tin, employee.hdmf], backTextSetting('governmentNumbers').x, backTextSetting('governmentNumbers').y, 35.1)}
        </text>
        <text x="${backTextSetting('emergencyName').x}" y="${backTextSetting('emergencyName').y}"
              text-anchor="middle" class="hyundai-bold" fill="#000"
              font-size="${backTextSetting('emergencyName').fontSize}" ${backTextAttributes('emergencyName')}>${escapeXml(employee.emergencyName)}</text>
        <text text-anchor="middle" class="hyundai-bold" fill="#000"
              font-size="${backTextSetting('emergencyAddress').fontSize}" ${backTextAttributes('emergencyAddress')}>
            ${tspans(ntrEmergencyAddressLines, backTextSetting('emergencyAddress').x, backTextSetting('emergencyAddress').y, 25)}
        </text>
        <text x="${backTextSetting('emergencyNumber').x}" y="${backTextSetting('emergencyNumber').y}"
              text-anchor="middle" class="hyundai-bold" fill="#000"
              font-size="${backTextSetting('emergencyNumber').fontSize}" ${backTextAttributes('emergencyNumber')}>${escapeXml(employee.emergencyNumber)}</text>
    </svg>`;

    const createBack = () => isNtrTemplate()
        ? createNtrBack()
        : isFusoTemplate()
            ? createFusoBack()
            : createMitsubishiBack();

    const renderFront = () => {
        document.getElementById('frontContainer').innerHTML = createFront();
    };

    const renderBack = () => {
        document.getElementById('backContainer').innerHTML = createBack();
    };

    renderFront();
    renderBack();

    const editSideSelect = document.querySelector('[data-id-edit-side]');
    const editSideElements = [...document.querySelectorAll('[data-id-preview-side], [data-id-editor-side]')];
    const selectEditSide = side => {
        const selectedSide = side === 'back' ? 'back' : 'front';
        for (const element of editSideElements) {
            const elementSide = element.dataset.idPreviewSide || element.dataset.idEditorSide;
            element.hidden = elementSide !== selectedSide;
        }
        if (editSideSelect) editSideSelect.value = selectedSide;
    };

    editSideSelect?.addEventListener('change', () => selectEditSide(editSideSelect.value));
    selectEditSide(editSideSelect?.value);

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
        if (input.value === '') return;
        const key = input.dataset.photoControl;
        const value = Number(input.value);
        if (Number.isFinite(value)) {
            updatePhotoPlacement({ [key]: value });
        }
    }));
    photoInputs.forEach(input => input.addEventListener('change', () => {
        if (input.value === '') syncPhotoControls();
    }));

    document.querySelector('[data-photo-reset]')?.addEventListener('click', () => {
        Object.assign(photoPlacement, defaultPhotoPlacement());
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

    const frontTextSelect = document.querySelector('[data-front-text-select]');
    const frontTextInputs = [...document.querySelectorAll('[data-front-text-control]')];
    const frontTextOutputs = [...document.querySelectorAll('[data-front-text-output]')];

    const populateFrontTextSelect = () => {
        const definitions = frontTextDefinitions();
        const keys = Object.keys(definitions);
        if (!keys.includes(selectedFrontTextKey)) {
            selectedFrontTextKey = keys[0];
        }

        frontTextSelect.replaceChildren(...Object.entries(definitions).map(([key, definition]) => {
            const option = document.createElement('option');
            option.value = key;
            option.textContent = definition.label;
            return option;
        }));
        frontTextSelect.value = selectedFrontTextKey;
    };

    const syncFrontTextControls = () => {
        const setting = frontTextSetting(selectedFrontTextKey);
        if (!setting) return;

        for (const input of frontTextInputs) {
            input.value = String(setting[input.dataset.frontTextControl]);
        }
        for (const output of frontTextOutputs) {
            const key = output.dataset.frontTextOutput;
            const value = setting[key];
            output.value = key === 'fontSize'
                ? `${Number(value).toFixed(1)} px`
                : `${Math.round(value)} px`;
        }
    };

    const selectFrontText = key => {
        if (!frontTextSettings[key]) return;
        selectedFrontTextKey = key;
        frontTextSelect.value = key;
        syncFrontTextControls();
    };

    // Re-rendering the front rebuilds the photo nodes too, so the photo placement has to
    // be re-applied to the fresh markup after every text change.
    const renderFrontWithPhoto = () => {
        renderFront();
        applyPhotoPlacement();
    };

    const updateFrontText = (changes, persist = true) => {
        const defaults = frontTextDefinitions()[selectedFrontTextKey];
        if (!defaults) return;
        frontTextSettings[selectedFrontTextKey] = normalizeFrontTextSetting({
            ...frontTextSettings[selectedFrontTextKey],
            ...changes,
        }, defaults);
        renderFrontWithPhoto();
        syncFrontTextControls();
        if (persist) saveFrontTextSettings();
    };

    populateFrontTextSelect();
    syncFrontTextControls();

    frontTextSelect.addEventListener('change', () => selectFrontText(frontTextSelect.value));
    frontTextInputs.forEach(input => input.addEventListener('input', () => {
        if (input.value === '') return;
        const value = Number(input.value);
        if (Number.isFinite(value)) {
            updateFrontText({ [input.dataset.frontTextControl]: value });
        }
    }));
    frontTextInputs.forEach(input => input.addEventListener('change', () => {
        if (input.value === '') syncFrontTextControls();
    }));

    document.querySelector('[data-front-text-reset]')?.addEventListener('click', () => {
        const defaults = frontTextDefinitions()[selectedFrontTextKey];
        if (!defaults) return;
        frontTextSettings[selectedFrontTextKey] = normalizeFrontTextSetting(defaults, defaults);
        renderFrontWithPhoto();
        syncFrontTextControls();
        saveFrontTextSettings();
    });

    document.querySelector('[data-front-text-reset-all]')?.addEventListener('click', () => {
        for (const [key, defaults] of Object.entries(frontTextDefinitions())) {
            frontTextSettings[key] = normalizeFrontTextSetting(defaults, defaults);
        }
        renderFrontWithPhoto();
        syncFrontTextControls();
        saveFrontTextSettings();
    });

    let frontTextDrag = null;
    frontContainer.addEventListener('pointerdown', event => {
        if (!(event.target instanceof Element)) return;
        const text = event.target.closest('[data-front-text-key]');
        if (!text) return;

        const key = text.dataset.frontTextKey;
        selectFrontText(key);
        const point = cardPointFromPointer(event);
        const setting = frontTextSetting(key);
        frontTextDrag = {
            pointerId: event.pointerId,
            pointX: point.x,
            pointY: point.y,
            textX: setting.x,
            textY: setting.y,
        };
        frontContainer.classList.add('is-front-text-dragging');
        event.preventDefault();
    });

    document.addEventListener('pointermove', event => {
        if (!frontTextDrag || event.pointerId !== frontTextDrag.pointerId) return;
        const point = cardPointFromPointer(event);
        updateFrontText({
            x: Math.round(frontTextDrag.textX + point.x - frontTextDrag.pointX),
            y: Math.round(frontTextDrag.textY + point.y - frontTextDrag.pointY),
        }, false);
        event.preventDefault();
    });

    const finishFrontTextDrag = event => {
        if (!frontTextDrag || event.pointerId !== frontTextDrag.pointerId) return;
        frontTextDrag = null;
        frontContainer.classList.remove('is-front-text-dragging');
        saveFrontTextSettings();
    };

    document.addEventListener('pointerup', finishFrontTextDrag);
    document.addEventListener('pointercancel', finishFrontTextDrag);

    const backContainer = document.getElementById('backContainer');
    const backTextSelect = document.querySelector('[data-back-text-select]');
    const backTextInputs = [...document.querySelectorAll('[data-back-text-control]')];
    const backTextOutputs = [...document.querySelectorAll('[data-back-text-output]')];

    const populateBackTextSelect = () => {
        const definitions = backTextDefinitions();
        const keys = Object.keys(definitions);
        if (!keys.includes(selectedBackTextKey)) {
            selectedBackTextKey = keys[0];
        }

        backTextSelect.replaceChildren(...Object.entries(definitions).map(([key, definition]) => {
            const option = document.createElement('option');
            option.value = key;
            option.textContent = definition.label;
            return option;
        }));
        backTextSelect.value = selectedBackTextKey;
    };

    const syncBackTextControls = () => {
        const setting = backTextSetting(selectedBackTextKey);
        if (!setting) return;

        for (const input of backTextInputs) {
            input.value = String(setting[input.dataset.backTextControl]);
        }
        for (const output of backTextOutputs) {
            const key = output.dataset.backTextOutput;
            const value = setting[key];
            output.value = key === 'fontSize'
                ? `${Number(value).toFixed(1)} px`
                : `${Math.round(value)} px`;
        }
    };

    const selectBackText = key => {
        if (!backTextSettings[key]) return;
        selectedBackTextKey = key;
        backTextSelect.value = key;
        syncBackTextControls();
    };

    const updateBackText = (changes, persist = true) => {
        const defaults = backTextDefinitions()[selectedBackTextKey];
        if (!defaults) return;
        backTextSettings[selectedBackTextKey] = normalizeBackTextSetting({
            ...backTextSettings[selectedBackTextKey],
            ...changes,
        }, defaults);
        renderBack();
        syncBackTextControls();
        if (persist) saveBackTextSettings();
    };

    populateBackTextSelect();
    syncBackTextControls();

    backTextSelect.addEventListener('change', () => selectBackText(backTextSelect.value));
    backTextInputs.forEach(input => input.addEventListener('input', () => {
        if (input.value === '') return;
        const value = Number(input.value);
        if (Number.isFinite(value)) {
            updateBackText({ [input.dataset.backTextControl]: value });
        }
    }));
    backTextInputs.forEach(input => input.addEventListener('change', () => {
        if (input.value === '') syncBackTextControls();
    }));

    document.querySelector('[data-back-text-reset]')?.addEventListener('click', () => {
        const defaults = backTextDefinitions()[selectedBackTextKey];
        if (!defaults) return;
        backTextSettings[selectedBackTextKey] = normalizeBackTextSetting(defaults, defaults);
        renderBack();
        syncBackTextControls();
        saveBackTextSettings();
    });

    document.querySelector('[data-back-text-reset-all]')?.addEventListener('click', () => {
        backTextSettings = loadBackTextSettings();
        for (const [key, defaults] of Object.entries(backTextDefinitions())) {
            backTextSettings[key] = normalizeBackTextSetting(defaults, defaults);
        }
        renderBack();
        syncBackTextControls();
        saveBackTextSettings();
    });

    const cardPointFromBackPointer = event => {
        const svg = document.getElementById('idBack');
        const bounds = svg.getBoundingClientRect();
        return {
            x: ((event.clientX - bounds.left) / bounds.width) * CARD_WIDTH,
            y: ((event.clientY - bounds.top) / bounds.height) * CARD_HEIGHT,
        };
    };

    let backTextDrag = null;
    backContainer.addEventListener('pointerdown', event => {
        if (!(event.target instanceof Element)) return;
        const text = event.target.closest('[data-back-text-key]');
        if (!text) return;

        const key = text.dataset.backTextKey;
        selectBackText(key);
        const point = cardPointFromBackPointer(event);
        const setting = backTextSetting(key);
        backTextDrag = {
            pointerId: event.pointerId,
            pointX: point.x,
            pointY: point.y,
            textX: setting.x,
            textY: setting.y,
        };
        backContainer.classList.add('is-back-text-dragging');
        event.preventDefault();
    });

    document.addEventListener('pointermove', event => {
        if (!backTextDrag || event.pointerId !== backTextDrag.pointerId) return;
        const point = cardPointFromBackPointer(event);
        updateBackText({
            x: Math.round(backTextDrag.textX + point.x - backTextDrag.pointX),
            y: Math.round(backTextDrag.textY + point.y - backTextDrag.pointY),
        }, false);
        event.preventDefault();
    });

    const finishBackTextDrag = event => {
        if (!backTextDrag || event.pointerId !== backTextDrag.pointerId) return;
        backTextDrag = null;
        backContainer.classList.remove('is-back-text-dragging');
        saveBackTextSettings();
    };

    document.addEventListener('pointerup', finishBackTextDrag);
    document.addEventListener('pointercancel', finishBackTextDrag);

    const templateSelect = document.querySelector('[data-id-template]');
    if (templateSelect) {
        templateSelect.value = selectedTemplateKey;
        templateSelect.addEventListener('change', () => {
            if (!availableTemplateKeys.includes(templateSelect.value)) {
                return;
            }

            const previousPhotoProfile = photoPlacementProfile();
            selectedTemplateKey = templateSelect.value;
            if (photoPlacementProfile() !== previousPhotoProfile) {
                Object.assign(photoPlacement, loadPhotoPlacement());
            }
            frontTextSettings = loadFrontTextSettings();
            selectedFrontTextKey = Object.keys(frontTextSettings)[0];
            backTextSettings = loadBackTextSettings();
            selectedBackTextKey = Object.keys(backTextSettings)[0];
            try {
                window.localStorage.setItem(templateStorageKey, selectedTemplateKey);
            } catch (_) {
                // The selected template still works for the current page.
            }
            renderFront();
            renderBack();
            applyPhotoPlacement();
            populateFrontTextSelect();
            syncFrontTextControls();
            populateBackTextSelect();
            syncBackTextControls();
        });
    }

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
