doc:
	cd docs; bundle exec jekyll serve

chmod:
	find . -type f -exec chmod 664 {} \;
