<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Graphsql Diagram</title>

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }
        .sorting-menu {
            padding: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .sorting-select {
            font-size: 1rem;
            padding: 5px;
        }
        .table-list {
            display: flex;
            align-items: baseline;
            flex-wrap: wrap;
            gap: 2rem;
            padding: 15px;
        }
        .table {
            display: flex;
            flex-direction: column;
            min-width: 250px;
            max-width: 300px;
            border-radius: 5px;
            box-shadow: 5px 5px 10px 2px rgba(0, 0, 0, 0.3);
            margin-top: 10px;
        }
        .main-table {
            flex-grow: 1;
        }
        .table-name {
            padding: 5px;
            background: #636b6f;
            color: white;
        }
        .column-name {
            border-top: 1px solid #ddd;
            padding: 5px;
            color: #636b6f;
        }
        .node-list {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 5px;
            padding: 5px;
            border-top: 1px solid #ddd;
        }
        .node-title {
            padding: 3px 7px;
            background: #636b6f;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }
        .node-title:hover {
            background: #00567f;
        }
        .modal-wrapper {
            position: fixed;
            width: 80vw;
            height: 80vh;
            padding: 10vh 10vw;
            top: 0;
            left: 0;
            display: none;
        }
        .modal {
            padding: 20px;
            background: white;
            box-shadow: 5px 5px 10px 2px rgba(0, 0, 0, 0.3);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
        }
        .modal-close-btn {
            border-radius: 50%;
            background: gray;
            color: white;
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 50%;
            cursor: pointer;
        }
        .modal-body {
            max-height: 60vh;
            overflow-y: auto;
        }
        .animate-opacity-ping {
            animation: opacityPing 1s ease-in-out 2;
        }
        /* animations */
        @keyframes opacityPing {
            50% {
                opacity: 0.3;
            }
            100% {
                opacity: 1;
            }
        }

    </style>
</head>
<body>
<h1>GraphSQL Diagram</h1>
<div id="schema" data-schema="{{ json_encode($schema) }}"> </div>
<div class="sorting-menu">
    <div>Sort By</div>
    <select id="sorting_select" class="sorting-select">
        <option value="column_count" selected>Column Count</option>
        <option value="table_name">Table Name</option>
    </select>
</div>
<div id="content"></div>
<div id="modal_wrapper" class="modal-wrapper">
    <div id="modal" class="modal">
        <div class="modal-header">
            <div>Details</div>
            <div id="modal_close_btn" class="modal-close-btn">x</div>
        </div>
        <div id="modal_body" class="modal-body"></div>
    </div>
</div>
</body>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalContent = null
        const schema = JSON.parse(document.getElementById('schema').getAttribute('data-schema'))
        console.log(schema)

        const contentDom = document.getElementById('content')
        renderContent(contentDom, schema, 'column_count')
        showTableDetailsOnClick(schema, 'column_count')
        highlightRelatedTableOnClick()

        const sortingSelect = document.getElementById('sorting_select')
        sortingSelect.addEventListener('change', function (e) {
            renderContent(contentDom, schema, e.target.value)
            showTableDetailsOnClick(schema, e.target.value)
            highlightRelatedTableOnClick()
        })




    })

    function renderContent(dom, schema, sortBy, mainTable=null, idPrefix='') {
        console.log(sortBy)
        schema.sort((a,b) => {
            return sortBy === 'column_count' ?
                a.fields.length - b.fields.length
                : a.table.localeCompare(b.table)
        })
        if (mainTable) {
            const filtered = schema.filter(s => s.table === mainTable)
            if (filtered.length) {
                schema = schema.filter(s => s.table !== mainTable)
                mainTable = filtered[0]
            }
        }

        let html = ``
        if (mainTable) {
            html += `
                <div class="table-list">
                    <div class="main-table">
                        <div><strong>Table:</strong> ${mainTable.table}</div>
                        <div><strong>Columns:</strong> ${mainTable.fields.map(field => field).join(', ')}</div>
                        <div><strong>Special:</strong> ${mainTable.specialFields.map(field => field).join(', ')}</div>
                        <div><strong>Nodes:</strong> </div>
                        <ol style="margin: 0">
                            ${mainTable.nodes.map(node => `<li><strong>${node.title}</strong> | <strong>${node.table}</strong> (Table) ${node.pivot ? `| <strong>${node.pivot}</strong> (Pivot) ` : ''}</li>`).join('')}
                        </ol>

                    </div>
            `
            html += generateTableCard(mainTable, idPrefix)
            html += `</div> <hr>`
        }

        html += `<div class="table-list">`
        schema.forEach(entity => {
            html += generateTableCard(entity, idPrefix)
        })
        html += `</div>`

        dom.innerHTML = html
    }

    function generateTableCard(entity, idPrefix="") {
        let html = `
                <div class="table" id="${idPrefix}${entity.table}" data-table="${entity.table}" data-node-count="${entity.nodes.length}" ${entity.nodes.length ? 'style="cursor: pointer;"' : ''}>
                    <div class="table-name">${entity.table} (${entity.fields.length})</div>
                    <div class="column-list">
            `
        entity.fields.forEach(field => {
            html += `
                    <div class="column-name">${field}</div>
                `
        })

        html += `
                    </div>
           `
        if (entity.nodes.length) {
            html += `
                    <div class="node-list">
                        <div>Rel: </div>
                `
            entity.nodes.forEach(node => {
                html += `
                    <div class="node-title" data-table="${node.table}">
                        ${node.title}
                    </div>
                `
            })
            html += `
                    </div>
                `
        }
        html += `
                </div>
            `
        return html
    }

    function showTableDetailsOnClick(schema, sortBy) {
        const modalCloseBtn = document.getElementById('modal_close_btn')
        const modalWrapper = document.getElementById('modal_wrapper')
        const modalBody = document.getElementById('modal_body')
        const tables = document.querySelectorAll('.table')
        tables.forEach(table => {
            table.addEventListener('click', function () {
                const tableName = table.getAttribute('data-table')
                const nodeCount = Number(table.getAttribute('data-node-count'))
                if (nodeCount) {
                    const filteredSchema = schema.filter(s => s.table === tableName)
                    const filteredSchema2 = schema.filter(s => filteredSchema[0].nodes.filter(n => n.table === s.table || n.pivot === s.table).length)
                    const newSchema = [...filteredSchema, ...filteredSchema2]
                    renderContent(modalBody, newSchema, sortBy, tableName, 'modal_')
                    modalWrapper.style.display = 'block'
                    showTableDetailsOnClick(schema, sortBy)
                }
            })
        })
        modalCloseBtn.addEventListener('click', function () {
            modalWrapper.style.display = 'none'
        })
    }

    function highlightRelatedTableOnClick() {
        const nodeTitles = document.querySelectorAll('.node-title')
        nodeTitles.forEach(nodeTitle => {
            nodeTitle.addEventListener('click', function (e) {
                e.stopPropagation()
                const targetTable = nodeTitle.getAttribute('data-table')
                const targetTableDom = document.getElementById(targetTable)
                if (targetTableDom) {
                    targetTableDom.scrollIntoView({ behavior: "smooth", block: "center" })
                    targetTableDom.classList.add('animate-opacity-ping')
                    nodeTitle.classList.add('animate-opacity-ping')
                    setTimeout(() => {
                        targetTableDom.classList.remove('animate-opacity-ping')
                        nodeTitle.classList.remove('animate-opacity-ping')
                    }, 3000)
                }
            })
        })
    }
</script>
</html>
