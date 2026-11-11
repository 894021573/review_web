<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>复习数据管理</title>
    <!-- Bootstrap5 CDN -->
    <link href="/view/bootstrap@5.3.3.min.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            /* 全局暗色 */
            background-color: #121212;
            color: #e9ecef;
        }
        .sidebar {
            width: 240px;
            background-color: #1e1e1e;
        }
        .sidebar h5 {
            color: #ffffff;
        }
        .sidebar a {
            color: #adb5bd;
        }
        .sidebar a.active, .sidebar a:hover {
            background-color: #2563eb;
            color: #fff;
        }
        /* 主内容区域暗色 */
        .flex-grow-1 {
            background-color: #121212 !important;
        }
        /* 卡片暗色 */
        .card {
            background-color: #1e1e1e;
            color: #e9ecef;
        }
        .card-body {
            background-color: transparent;
        }
        /* 表格暗色覆盖 */
        .table {
            --bs-table-bg: #1e1e1e;
            --bs-table-color: #e9ecef;
            --bs-table-border-color: #495057;
        }
        /* 重点修复：hover 只改背景，文字保持浅色不变 */
        .table-hover > tbody > tr:hover {
            --bs-table-hover-bg: #7bbbc9;
            color: #e9ecef !important;
        }
        .table-primary {
            --bs-table-bg: #1e3a5f;
            --bs-table-color: #ffffff;
            --bs-table-border-color: #495057;
        }
    </style>
</head>
<body class="d-flex flex-row m-0 p-0">
<!-- 左侧侧边导航 -->
<div class="sidebar d-flex flex-column p-3">
    <h5 class="text-white mb-4">菜单导航</h5>
    <nav class="nav flex-column">
        <a href="?" class="nav-link rounded <?= empty($_GET['m']) ? 'active' : ''?>">所有</a>
        <?php if (!empty($navs)): ?>
            <?php foreach ($navs as $v): ?>
                <a href="?c=<?= $v['c'] ?>&m=<?= $v['m'] ?>&n=<?= $v['n'] ?>" class="nav-link rounded <?= $_GET['m'] === $v['m'] ? 'active' : '' ?>"><?= $v['m'] ?></a>
            <?php endforeach; ?>
        <?php else: ?>
            <span class="text-secondary">暂无数据</span>
        <?php endif; ?>
    </nav>
</div>

<!-- 右侧主内容区域 -->
<div class="flex-grow-1 overflow-auto p-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-primary">
                <tr>
                    <th>ID</th>
                    <th>分类</th>
                    <th>主标题</th>
                    <th>次标题</th>
                    <th>最近复习日期</th>
                    <th>当前复习次数<br>(重置影响艾宾日期)</th>
                    <th>总复习次数</th>
                    <th>距今天数</th>
                    <th>优先级</th>
                    <th>艾宾复习日期</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($list)): ?>
                    <?php foreach ($list as $v): ?>
                        <tr>
                            <td><?= $v['id'] ?></td>
                            <td><?= $v['category'] ?></td>
                            <td><?= $v['main_title'] ?></td>
                            <td><?= $v['sub_title'] ?></td>
                            <td><?= $v['last_review_date'] ?></td>
                            <td><?= $v['review_count'] ?>
                            <br>
                                <a class="btn btn-warning btn-sm" href="?action=reset_review&id=<?= $v['id']?>&c=<?= $v['category'] ?>&m=<?= $v['main_title'] ?>" onclick="return confirm('确定要重置次数吗？');">重置次数</a>
                            </td>
                            <td><?= $v['total_review_count'] ?></td>
                            <td><?= $v['days'] ?></td>
                            <td><?= round($v['priority'],2) ?></td>
                            <td>
                                <?php
                                if (strtotime($v['abhs_date']) <= time()) {
                                    echo "<span class='text-danger'>{$v['abhs_date']}</span>";
                                } else {
                                    echo $v['abhs_date'];
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                if ($v['last_review_date'] == date('Y-m-d')):
                                    ?>
                                    <a class="btn btn-danger btn-sm" href="?action=cancel_review&id=<?= $v['id']?>&c=<?= $v['category'] ?>&m=<?= $v['main_title'] ?>" onclick="return confirm('确定要撤销今日复习吗？');">撤销今日复习</a>
                                <?php
                                else:
                                ?>
                                    <a class="btn btn-success btn-sm" href="?action=done_review&id=<?= $v['id']?>&c=<?= $v['category'] ?>&m=<?= $v['main_title'] ?>" onclick="return confirm('确定要完成今日复习吗？');">完成今日复习</a>
                                <?php
                                endif;
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">暂无数据</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
