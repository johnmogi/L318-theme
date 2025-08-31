        <div class="wizard-actions">
            <?php if ($this->current_step > 1) : ?>
                <button type="submit" name="back" class="button button-secondary" value="1">
                    <?php _e('Back', 'hello-theme-child'); ?>
                </button>
            <?php endif; ?>
            
            <?php if ($this->current_step < $this->total_steps) : ?>
                <button type="submit" name="next" class="button button-primary" value="1">
                    <?php _e('Next', 'hello-theme-child'); ?>
                </button>
            <?php else : ?>
                <button type="submit" name="submit" class="button button-primary" value="1">
                    <?php _e('Generate Codes', 'hello-theme-child'); ?>
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>
