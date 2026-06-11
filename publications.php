<?php
$conn = mysqli_connect('localhost', 'root', '', 'club_db');
$publications = [];
if ($conn) {
    $res = mysqli_query($conn, "SELECT * FROM club_publications ORDER BY pub_year DESC, id DESC");
    while ($row = mysqli_fetch_assoc($res)) {
        $publications[] = $row;
    }
    mysqli_close($conn);
}
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>K-MiNDS | Publications</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="achievement-detail.css">
</head>
<body class="achievement-page publications publications-page">
    <div class="page-shell">
        <main>
            <section id="overview" class="hero-section">
                <div class="hero-grid">
                    <div class="hero-copy reveal-item" data-reveal>
                        <div class="kicker">Publications</div>
                        <h1>Research documented and shared widely.</h1>
                        <p>This page highlights the research papers and journals where K-MiNDS members have published original work in machine learning, data science, and AI applications.</p>
                        <div class="hero-actions">
                            <a class="action-btn primary" href="Landing_page.php#achievements">See all achievements</a>
                            <a class="action-btn secondary" href="#papers">Explore the papers</a>
                        </div>
                    </div>

                    <aside class="hero-panel reveal-item" data-reveal>
                        <div class="panel-badge">Research pipeline</div>
                        <div class="panel-stat">Experiment, draft, publish</div>
                        <p class="panel-copy">Strong publications start with focused research questions, rigorous methods, and clear communication. Each paper tells the story of a solved problem.</p>
                        <div class="stat-grid">
                            <div class="stat-card">
                                <strong>Peer review</strong>
                                <span>Papers go through evaluation to ensure quality and novelty.</span>
                            </div>
                            <div class="stat-card">
                                <strong>Wider impact</strong>
                                <span>Published work reaches researchers and practitioners globally.</span>
                            </div>
                        </div>
                    </aside>
                </div>
            </section>

            <section id="papers" class="content-section section-tone">
                <div class="section-heading reveal-item" data-reveal>
                    <div class="eyebrow">Published research</div>
                    <h2>Papers accepted and presented</h2>
                    <p>These publications reflect the club's depth in machine learning, feature engineering, and applied AI research. Each represents months of experimentation and refinement.</p>
                </div>

                <div class="card-grid">
                    <?php if (empty($publications)): ?>
                        <p style="color: var(--muted, #888); grid-column: 1 / -1; text-align: center; padding: 2rem 0;">
                            No publications have been added yet.
                        </p>
                    <?php else: ?>
                        <?php foreach ($publications as $i => $pub):
                            $delay   = $i * 120;
                            $bullets = [];
                            if (!empty($pub['bullets'])) {
                                $bullets = array_filter(array_map('trim', preg_split('/[\n\r]+/', $pub['bullets'])));
                            }
                        ?>
                        <article class="glass-card reveal-item" data-reveal style="--delay: <?= $delay ?>ms;">
                            <div class="card-label"><?= h($pub['pub_type']) ?></div>
                            <h3><?= h($pub['title']) ?></h3>

                            <?php if (!empty($pub['venue'])): ?>
                            <p>
                                <strong>
                                <?php
                                    $type = strtolower($pub['pub_type']);
                                    if ($type === 'journal')     echo 'Published in:';
                                    elseif ($type === 'conference' || $type === 'workshop') echo 'Presented at:';
                                    else echo 'Available on:';
                                ?>
                                </strong>
                                <?php if (!empty($pub['venue_url'])): ?>
                                    <a href="<?= h($pub['venue_url']) ?>" target="_blank" rel="noopener noreferrer"><?= h($pub['venue']) ?></a>
                                <?php else: ?>
                                    <?= h($pub['venue']) ?>
                                <?php endif; ?>
                            </p>
                            <?php endif; ?>

                            <p><strong>Year:</strong> <?= h($pub['pub_year']) ?></p>

                            <?php if (!empty($pub['topic'])): ?>
                            <p><strong>Topic:</strong> <?= h($pub['topic']) ?></p>
                            <?php endif; ?>

                            <?php if (!empty($bullets)): ?>
                            <ul>
                                <?php foreach ($bullets as $b): ?>
                                    <li><?= h($b) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section id="writing" class="content-section">
                <div class="section-heading reveal-item" data-reveal>
                    <div class="eyebrow">Writing great papers</div>
                    <h2>How to craft a strong research publication</h2>
                    <p>Publishing research is a skill that improves with practice. Here are the core habits that help K-MiNDS papers stand out in peer review.</p>
                </div>

                <div class="detail-band">
                    <div class="timeline">
                        <div class="timeline-step reveal-item" data-reveal>
                            <span class="timeline-dot"></span>
                            <div>
                                <strong>Start with a clear research question</strong>
                                <p>Before writing code, define exactly what you want to learn. A focused question makes the whole paper easier to structure.</p>
                            </div>
                        </div>
                        <div class="timeline-step reveal-item" data-reveal>
                            <span class="timeline-dot"></span>
                            <div>
                                <strong>Review the related work thoroughly</strong>
                                <p>Read papers in your domain to understand what's already known and where your contribution fits.</p>
                            </div>
                        </div>
                        <div class="timeline-step reveal-item" data-reveal>
                            <span class="timeline-dot"></span>
                            <div>
                                <strong>Design reproducible experiments</strong>
                                <p>Document your methodology, hyperparameters, and datasets so others can verify your results.</p>
                            </div>
                        </div>
                        <div class="timeline-step reveal-item" data-reveal>
                            <span class="timeline-dot"></span>
                            <div>
                                <strong>Write clearly and iteratively</strong>
                                <p>Drafts improve through revision. Read aloud, get feedback from peers, and refine your language.</p>
                            </div>
                        </div>
                    </div>

                    <div class="highlight-list">
                        <article class="glass-card reveal-item" data-reveal>
                            <div class="card-label">Publication checklist</div>
                            <h3>Before submitting your paper</h3>
                            <p>A strong submission stands out because it checks these boxes consistently.</p>
                            <ul>
                                <li>Clear motivation and contribution statement</li>
                                <li>Thorough literature review</li>
                                <li>Detailed experimental setup</li>
                                <li>Results with statistical significance</li>
                                <li>Honest discussion of limitations</li>
                                <li>Reproducible code and data links</li>
                            </ul>
                        </article>
                    </div>
                </div>
            </section>

            <section id="process" class="content-section section-tone">
                <div class="section-heading reveal-item" data-reveal>
                    <div class="eyebrow">Publication tips</div>
                    <h2>Short version: rigor and clarity</h2>
                    <p>We publish by starting with a strong research idea, running solid experiments, writing carefully, and responding constructively to reviewer feedback.</p>
                </div>

                <div class="card-grid process-grid">
                    <article class="glass-card reveal-item" data-reveal style="--delay: 0ms;">
                        <div class="card-label">01</div>
                        <h3>Conduct rigorous research</h3>
                        <p>Use proper statistical methods, control for confounds, and test on multiple datasets to ensure your findings are robust.</p>
                    </article>
                    <article class="glass-card reveal-item" data-reveal style="--delay: 120ms;">
                        <div class="card-label">02</div>
                        <h3>Write a compelling narrative</h3>
                        <p>Guide readers through your motivation, methods, and results in a logical flow that builds the case for your contribution.</p>
                    </article>
                    <article class="glass-card reveal-item" data-reveal style="--delay: 240ms;">
                        <div class="card-label">03</div>
                        <h3>Welcome peer feedback</h3>
                        <p>Reviewers strengthen your work. Treat feedback as a gift and use it to clarify and improve your research story.</p>
                    </article>
                </div>
            </section>
        </main>

        <footer class="footer-band">
            <div class="footer-cta reveal-item" data-reveal>
                <div>
                    <strong>Explore more achievements</strong>
                    <p>Return to the landing page to see competitions, and other club milestones.</p>
                </div>
                <a class="action-btn primary" href="Landing_page.php#achievements">Return to showcase</a>
            </div>
        </footer>
    </div>

    <script src="achievement-detail.js"></script>
</body>
</html>

