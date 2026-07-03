export const exportToExcel = (target, filename = 'report') => {
    const table = typeof target === 'string' 
        ? (document.getElementById(target) || document.querySelector(target)) 
        : target;
    if (!table) return;
    
    let html = table.outerHTML;
    // Clean interactive form elements (checkboxes, action buttons, dropdowns)
    html = html.replace(/<button[^>]*>([\s\S]*?)<\/button>/gi, '');
    html = html.replace(/<input[^>]*>/gi, '');
    html = html.replace(/<select[^>]*>([\s\S]*?)<\/select>/gi, '');
    
    const template = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <!--[if gte mso 9]>
            <xml>
                <x:ExcelWorkbook>
                    <x:ExcelWorksheets>
                        <x:ExcelWorksheet>
                            <x:Name>Report Sheet</x:Name>
                            <x:WorksheetOptions>
                                <x:DisplayGridlines/>
                            </x:WorksheetOptions>
                        </x:ExcelWorksheet>
                    </x:ExcelWorksheets>
                </x:ExcelWorkbook>
            </xml>
            <![endif]-->
            <style>
                table { border-collapse: collapse; width: 100%; font-family: sans-serif; font-size: 11px; }
                th { background-color: #4f46e5; color: #ffffff; font-weight: bold; border: 1px solid #cbd5e1; padding: 6px; text-transform: uppercase; }
                td { border: 1px solid #e2e8f0; padding: 6px; text-align: left; }
                tr:nth-child(even) td { background-color: #f8fafc; }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .font-mono { font-family: monospace; }
            </style>
        </head>
        <body>
            ${html}
        </body>
        </html>
    `;
    
    const blob = new Blob([template], { type: 'application/vnd.ms-excel' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${filename}_${new Date().toISOString().slice(0, 10)}.xls`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
};

export const printReport = ({ title, filters, tableId, schoolInfo }) => {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let tableHtml = table.outerHTML;
    // Clean interactive headers & buttons
    tableHtml = tableHtml.replace(/<button[^>]*>([\s\S]*?)<\/button>/gi, '');
    tableHtml = tableHtml.replace(/<input[^>]*>/gi, '');
    tableHtml = tableHtml.replace(/<select[^>]*>([\s\S]*?)<\/select>/gi, '');
    
    const printWindow = window.open('', '_blank', 'width=900,height=600');
    if (!printWindow) return;
    
    const schoolName = schoolInfo?.name || 'EduvoraX School';
    const schoolAddress = schoolInfo?.address || '';
    const schoolLogo = schoolInfo?.logo ? `<img src="${schoolInfo.logo}" style="max-height: 55px; float: left; margin-right: 15px; border-radius: 8px;" />` : '';
    
    const filterDetails = Object.entries(filters || {})
        .filter(([k, v]) => v !== undefined && v !== null && v !== '')
        .map(([k, v]) => `<span style="margin-right: 15px;"><strong>${k.toUpperCase().replace('_', ' ')}:</strong> ${v}</span>`)
        .join('');
        
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>${title}</title>
            <style>
                body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1e293b; font-size: 10px; line-height: 1.4; margin: 25px; }
                .header-table { width: 100%; border-collapse: collapse; border-bottom: 2px solid #cbd5e1; margin-bottom: 20px; padding-bottom: 12px; }
                .school-name { font-size: 16px; font-weight: bold; color: #4f46e5; text-transform: uppercase; margin: 0; }
                .school-info { font-size: 9px; color: #64748b; margin: 2px 0 0 0; }
                .report-title { font-size: 13px; font-weight: 800; text-transform: uppercase; color: #0f172a; margin: 15px 0 5px 0; }
                .report-meta { text-align: right; font-size: 8px; color: #64748b; }
                .filter-scope { font-size: 9px; color: #475569; margin-bottom: 15px; background: #f8fafc; padding: 6px 10px; border-radius: 6px; border: 1px solid #e2e8f0; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th { background-color: #f8fafc; border-bottom: 2.5px solid #cbd5e1; padding: 8px 10px; font-weight: bold; color: #475569; text-transform: uppercase; font-size: 8px; text-align: left; }
                td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; color: #334155; }
                tr:nth-child(even) td { background-color: #f8fafc; }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .font-mono { font-family: monospace; }
                @media print {
                    body { margin: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <table class="header-table" style="width:100%;">
                <tr style="background: none;">
                    <td style="border: none; padding: 0; vertical-align: middle;">
                        ${schoolLogo}
                        <div class="school-name">${schoolName}</div>
                        <div class="school-info">${schoolAddress}</div>
                    </td>
                    <td style="border: none; padding: 0; text-align: right; vertical-align: middle;" class="report-meta">
                        <div>Report Type: <strong>Consolidated Summary</strong></div>
                        <div>Generated on: <strong>${new Date().toLocaleString()}</strong></div>
                    </td>
                </tr>
            </table>
            
            <div class="report-title">${title}</div>
            
            ${filterDetails ? `<div class="filter-scope">Filter Scope: ${filterDetails}</div>` : ''}
            
            <div>
                ${tableHtml}
            </div>
            
            <div style="margin-top: 40px; border-top: 1px solid #cbd5e1; padding-top: 8px; font-size: 8px; color: #94a3b8; text-align: center;">
                Generated by EduvoraX ERP. Page 1 of 1
            </div>
            
            <script>
                window.onload = function() {
                    window.print();
                    setTimeout(function() { window.close(); }, 500);
                };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
};
