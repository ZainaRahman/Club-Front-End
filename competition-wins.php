<?php
$conn = mysqli_connect('localhost', 'root', '', 'club_db');
$competition_wins = [];
if ($conn) {
    $res = mysqli_query($conn, "SELECT * FROM club_competition_wins ORDER BY win_date DESC");
    while ($row = mysqli_fetch_assoc($res)) {
        $competition_wins[] = $row;
    }
    mysqli_close($conn);
}
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Map result text to a display label
function result_label($result) {
    $r = strtolower(trim($result));
    if (str_contains($r, 'champion') || str_contains($r, '1st') || str_contains($r, 'first') || str_contains($r, 'winner')) return 'Champion';
    if (str_contains($r, 'runner') || str_contains($r, '2nd') || str_contains($r, 'second')) return 'Runners up';
    if (str_contains($r, 'international')) return 'International';
    return htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>K-MiNDS | Competition Wins</title>
    <link rel="stylesheet" href="achievement-detail.css">
</head>
<body class="achievement-page competition-wins competition-wins-page">
    <div class="page-shell">
        <main>
            <section id="overview" class="hero-section">
                <div class="hero-grid">
                    <div class="hero-copy reveal-item" data-reveal>
                        <div class="kicker">Competition wins</div>
                        <h1>Results shaped by focus, speed, and clarity.</h1>
                        <p>This page highlights the competitions where K-MiNDS earned top finishes through strong problem framing, practical machine learning, and confident presentation.</p>
                        <div class="hero-actions">
                            <a class="action-btn primary" href="Landing_page.php#achievements">See all achievements</a>
                            <a class="action-btn secondary" href="#wins">Explore the wins</a>
                        </div>
                    </div>

                    <aside class="hero-panel reveal-item" data-reveal>
                        <div class="panel-badge">Winning pattern</div>
                        <div class="panel-stat">Strategy + execution</div>
                        <p class="panel-copy">The strongest results came from a repeatable workflow: scope the problem, build the model early, and refine the pitch until it is easy to understand.</p>
                        <div class="stat-grid">
                            <div class="stat-card">
                                <strong>Problem first</strong>
                                <span>Each project started with a clear challenge and a measurable outcome.</span>
                            </div>
                            <div class="stat-card">
                                <strong>Fast iteration</strong>
                                <span>Models, features, and visuals were improved in short feedback loops.</span>
                            </div>
                        </div>
                    </aside>
                </div>
            </section>

            <section id="wins" class="content-section section-tone">
                <div class="section-heading reveal-item" data-reveal>
                    <div class="eyebrow">Competition results</div>
                    <h2>The competitions that were won</h2>
                    <p>These results reflect different problem spaces, but the same habit of turning complex data into a polished, competition-ready answer.</p>
                </div>

                <div class="card-grid">
                    <?php if (empty($competition_wins)): ?>
                        <p style="color: var(--muted, #888); grid-column: 1 / -1; text-align: center; padding: 2rem 0;">
                            No competition wins have been added yet.
                        </p>
                    <?php else: ?>
                        <?php foreach ($competition_wins as $i => $win):
                            $delay = $i * 120;
                            $label = result_label($win['result']);
                            $year  = date('Y', strtotime($win['win_date']));
                            $desc  = trim($win['description'] ?? '');
                            $bullets = $desc !== ''
                                ? array_filter(array_map('trim', preg_split('/[\n\r;|]+/', $desc)))
                                : [];
                        ?>
                        <article class="glass-card reveal-item" data-reveal style="--delay: <?= $delay ?>ms;">
                            <div class="card-label"><?= h($label) ?></div>
                            <h3><?= h($win['competition_name']) ?></h3>
                            <p><strong>Year:</strong> <?= h($year) ?></p>
                            <p><strong>Result:</strong> <?= h($win['result']) ?></p>
                            <?php if (!empty($bullets)): ?>
                            <ul>
                                <?php foreach ($bullets as $bullet): ?>
                                    <li><?= h($bullet) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section id="international" class="content-section">
                <div class="section-heading reveal-item" data-reveal>
                    <div class="eyebrow">International section</div>
                    <h2>VIPCUP 2026 and the bigger stage</h2>
                    <p>International competition usually rewards teams that can adapt fast, explain their choices clearly, and show a solution that feels ready for real use.</p>
                </div>

                <div class="detail-band">
                    <div class="timeline">
                        <?php
                        $featured = !empty($competition_wins) ? $competition_wins[0] : null;
                        if ($featured):
                            $featYear = date('Y', strtotime($featured['win_date']));
                        ?>
                        <div class="timeline-step reveal-item" data-reveal>
                            <span class="timeline-dot"></span>
                            <div>
                                <strong><?= h($featured['competition_name']) ?> <?= h($featYear) ?> — <?= h($featured['result']) ?></strong>
                                <p><?= h(mb_strimwidth($featured['description'] ?? '', 0, 160, '…')) ?></p>
                            </div>
                        </div>
                        <div class="timeline-step reveal-item" data-reveal>
                            <span class="timeline-dot"></span>
                            <div>
                                <strong>What stood out</strong>
                                <p>The solution balanced technical depth with a clear story, so the jury could quickly see both the model and the value.</p>
                            </div>
                        </div>
                        <div class="timeline-step reveal-item" data-reveal>
                            <span class="timeline-dot"></span>
                            <div>
                                <strong>Why it mattered</strong>
                                <p>Placing highly in a competition shows the team can deliver under pressure and compete beyond local events.</p>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="timeline-step reveal-item" data-reveal>
                            <span class="timeline-dot"></span>
                            <div>
                                <strong>No entries yet</strong>
                                <p>Competition wins will appear here once added from the admin panel.</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="highlight-list">
                        <article class="glass-card reveal-item" data-reveal>
                            <div class="card-label">Key takeaway</div>
                            <h3>International judging rewards clarity</h3>
                            <p>Winning on an international stage is not only about technical accuracy. It is also about making the solution understandable, credible, and easy to present in a short time.</p>
                            <ul>
                                <li>Compact but strong explanation</li>
                                <li>Robust model choices</li>
                                <li>Confidence in delivery</li>
                            </ul>
                        </article>
                    </div>
                </div>
            </section>

            <section id="process" class="content-section section-tone">
                <div class="section-heading reveal-item" data-reveal>
                    <div class="eyebrow">How we achieve</div>
                    <h2>Short version: disciplined teamwork</h2>
                    <p>We usually win by keeping the process simple and repeatable: understand the brief, clean the data, engineer useful features, train and compare models, then turn the result into a concise story.</p>
                </div>

                <div class="card-grid process-grid">
                    <article class="glass-card reveal-item" data-reveal style="--delay: 0ms;">
                        <div class="card-label">01</div>
                        <h3>Read the problem carefully</h3>
                        <p>We start by narrowing the scope and deciding exactly what success should look like before building anything.</p>
                    </article>
                    <article class="glass-card reveal-item" data-reveal style="--delay: 120ms;">
                        <div class="card-label">02</div>
                        <h3>Build the model early</h3>
                        <p>Quick baselines help the team find the strongest approach faster and leave time for testing and improvement.</p>
                    </article>
                    <article class="glass-card reveal-item" data-reveal style="--delay: 240ms;">
                        <div class="card-label">03</div>
                        <h3>Polish the final pitch</h3>
                        <p>The last step is making the idea easy to remember with a clean explanation, confident delivery, and a clear impact statement.</p>
                    </article>
                </div>
            </section>
        </main>

        <footer class="footer-band">
            <div class="footer-cta reveal-item" data-reveal>
                <div>
                    <strong>Back to the main showcase</strong>
                    <p>Return to the landing page to explore achievements, projects, and the rest of the club profile.</p>
                </div>
                <a class="action-btn primary" href="Landing_page.php#achievements">Return to showcase</a>
            </div>
        </footer>
    </div>

    <script src="achievement-detail.js"></script>
</body>
</html>