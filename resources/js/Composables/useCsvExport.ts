/**
 * Exportación a archivo delimitado compatible con Excel (CSV UTF-8 con BOM).
 * Se usa desde el botón XLSX del toolbar uniforme de los grids.
 */
export function downloadCsv(filename: string, headers: string[], rows: (string | number | null)[][]): void {
    const escape = (v: string | number | null) => {
        const s = String(v ?? '');
        return /[",\n;]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
    };

    const lines = [headers, ...rows].map((r) => r.map(escape).join(';'));
    // BOM para que Excel respete acentos y UTF-8.
    const blob = new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });

    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename.endsWith('.csv') ? filename : `${filename}.csv`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
}
