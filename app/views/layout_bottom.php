    </section>
    <footer class="footer"><span>Agriculture Information System</span><span>Cristel Mae Dionson · <?= date(
        'Y',
    ) ?></span></footer>
</main>
</div>
<dialog class="confirm-dialog" id="confirm-dialog" aria-labelledby="confirm-dialog-title" aria-describedby="confirm-dialog-message">
    <div class="confirm-dialog-panel">
        <div class="confirm-dialog-mark" aria-hidden="true">!</div>
        <div class="confirm-dialog-copy">
            <h2 id="confirm-dialog-title">Confirm deletion</h2>
            <p id="confirm-dialog-message"></p>
        </div>
        <div class="confirm-dialog-actions">
            <button class="button button-secondary" id="confirm-cancel" type="button" autofocus>Cancel</button>
            <button class="button button-danger" id="confirm-submit" type="button">Delete</button>
        </div>
    </div>
</dialog>
<script src="assets/js/app.js" defer></script>
</body></html>
