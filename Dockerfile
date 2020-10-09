FROM squidfunk/mkdocs-material

# Install custom plugins
RUN pip install \
    Pygments \
    mkdocs-git-revision-date-plugin \
    mkdocs-localsearch
