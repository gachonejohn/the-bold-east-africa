import React, { useEffect, useState, useCallback, useRef } from 'react';
import { useParams, Link } from 'react-router-dom';
import { api, getImageUrl, getLocalCache, setLocalCache, clearCache } from '../../services/api';
import { AdSlot } from '../AdSlot';
import { ArticleCard } from '../ArticleCard';
import { MatchFixturesWidget } from '../MatchFixturesWidget';

/**
 * Helper to create SEO-friendly slugs from titles
 */
const createSlug = (title: string) => {
  return (title || '')
    .toLowerCase()
    .trim()
    .replace(/[^\w\s-]/g, '')
    .replace(/[\s_-]+/g, '-')
    .replace(/^-+|-+$/g, '');
};

/**
 * ArticleDetailView Component
 *
 * Displays detailed view of a single article with related content.
 * Handles article fetching, view tracking, and related articles display.
 */
const ArticleDetailView: React.FC = () => {
  const { id } = useParams<{ id: string }>();

  // Load cached articles instantly from localStorage
  const cachedArticles = getLocalCache<any[]>('articles');
  const initialAll = cachedArticles?.data?.map((a: any) => ({
    ...a,
    date: a.date || (a.created_at ? new Date(a.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '')
  })) || [];

  // Try to resolve article from cache immediately
  const resolveArticleFromList = (all: any[], slug: string | undefined) => {
    if (!slug || all.length === 0) return null;
    if (!isNaN(Number(slug))) {
      return all.find((a: any) => String(a.id) === slug) || null;
    }
    return all.find((a: any) => a.slug === slug || createSlug(a.title) === slug) || null;
  };

  const cachedArticle = resolveArticleFromList(initialAll, id);

  const [article, setArticle] = useState<any>(cachedArticle);
  const [allArticles, setAllArticles] = useState<any[]>(initialAll);
  const [isLoading, setIsLoading] = useState(!cachedArticle);
  const refreshTimerRef = useRef<ReturnType<typeof setInterval> | null>(null);
  const viewTrackedRef = useRef<string | null>(null);

  const fetchAndResolve = useCallback(async (showSpinner = false) => {
    if (!navigator.onLine) return;
    if (showSpinner) setIsLoading(true);
    try {
      clearCache('articles');
      const articlesRes = await api.articles.getAll();
      const all = articlesRes.data.map((a: any) => ({
        ...a,
        date: a.date || (a.created_at ? new Date(a.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '')
      }));
      setAllArticles(all);
      setLocalCache('articles', articlesRes.data);

      // Resolve current article from fresh data
      let targetId = id;
      if (id && isNaN(Number(id))) {
        const foundArticle = all.find((a: any) =>
          a.slug === id || createSlug(a.title) === id
        );
        if (foundArticle) {
          targetId = foundArticle.id;
        } else {
          console.warn(`Article not found for slug: ${id}`);
          setIsLoading(false);
          return;
        }
      }

      if (targetId) {
        const articleRes = await api.articles.get(String(targetId));
        const data = articleRes.data;
        if (!data.date && data.created_at) {
          data.date = new Date(data.created_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        }
        setArticle(data);

        // Track view only once per article per page load
        if (viewTrackedRef.current !== String(targetId)) {
          viewTrackedRef.current = String(targetId);
          api.articles.trackView(targetId).catch(() => {});
        }
      }
    } catch (error) {
      console.error('Failed to fetch article', error);
    } finally {
      setIsLoading(false);
    }
  }, [id]);

  useEffect(() => {
    // Clear any existing timer
    if (refreshTimerRef.current) {
      clearInterval(refreshTimerRef.current);
      refreshTimerRef.current = null;
    }
    viewTrackedRef.current = null;

    if (cachedArticle) {
      // Cache hit: show instantly, refresh after 5s, then poll every 60s
      const initialTimer = setTimeout(() => fetchAndResolve(), 5000);
      refreshTimerRef.current = setInterval(() => fetchAndResolve(), 60000);
      // Track view for cached article
      if (cachedArticle.id && viewTrackedRef.current !== String(cachedArticle.id)) {
        viewTrackedRef.current = String(cachedArticle.id);
        api.articles.trackView(cachedArticle.id).catch(() => {});
      }
      return () => {
        clearTimeout(initialTimer);
        if (refreshTimerRef.current) clearInterval(refreshTimerRef.current);
      };
    } else {
      // No cache: fetch immediately with spinner, then poll every 60s
      fetchAndResolve(true).then(() => {
        refreshTimerRef.current = setInterval(() => fetchAndResolve(), 60000);
      });
      return () => {
        if (refreshTimerRef.current) clearInterval(refreshTimerRef.current);
      };
    }
  }, [id]);

  if (isLoading) {
    return (
      <div className="max-w-4xl mx-auto px-4 py-32 text-center">
        <div className="animate-pulse text-gray-400">Loading...</div>
      </div>
    );
  }

  if (!article) {
    return (
      <div className="max-w-4xl mx-auto px-4 py-32 text-center">
        <h2 className="text-3xl font-bold mb-6">Article Not Found</h2>
        <Link to="/" className="text-[#e5002b] font-bold uppercase tracking-widest hover:underline">
          Return to Home
        </Link>
      </div>
    );
  }

  // Find related articles by checking both primary category and categories array
  const relatedFromCategory = allArticles.filter(a => {
    if (a.id === article.id) return false;
    // Check if primary categories match
    if (a.category === article.category) return true;
    // Check if any category in the arrays overlap
    const articleCategories = article.categories || [article.category];
    const aCategories = a.categories || [a.category];
    return articleCategories.some((cat: string) => aCategories.includes(cat));
  }).slice(0, 4);
  const trendingArticles = allArticles.filter(a => a.id !== article.id).slice(0, 3);

  const processContent = (content: string) => {
    if (!content) return [];
    // Split by double newline for paragraphs
    const rawParagraphs = content.split(/\n\s*\n/);

    return rawParagraphs.map(p => {
      let processed = p.trim();

      // Images: ![alt](url)
      if (processed.startsWith('![') && processed.endsWith(')')) {
        const match = processed.match(/!\[(.*?)\]\((.*?)\)/);
        if (match) {
          return { type: 'image', src: match[2], alt: match[1] };
        }
      }

      // Bold: **text**
      processed = processed.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

      // Italic: _text_
      processed = processed.replace(/_(.*?)_/g, '<em>$1</em>');

      // Links: [text](url)
      processed = processed.replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" class="text-[#e5002b] hover:underline" target="_blank" rel="noopener noreferrer">$1</a>');

      // Blockquotes: > text
      if (processed.startsWith('> ')) {
          return { type: 'quote', content: processed.slice(2) };
      }

      return { type: 'paragraph', content: processed };
    });
  };

  return (
    <div className="bg-white min-h-screen">
      <main className="max-w-7xl mx-auto px-4 py-12 md:py-20">
        <div className="hidden md:block mb-12">
          <AdSlot type="leaderboard" />
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-16">
          <div className="lg:col-span-8">
            <article>
              <div className="flex items-center gap-3 mb-6">
                <Link to="/" className="text-xs font-bold uppercase text-gray-400 hover:text-black transition-colors">
                  Home
                </Link>
                <span className="text-gray-300 text-xs">/</span>
                <Link to={`/category/${article.category.toLowerCase()}`} className="text-xs font-bold uppercase text-[#e5002b] tracking-widest hover:underline">
                  {article.category}
                </Link>
              </div>

              <h1 className="text-2xl sm:text-4xl md:text-6xl lg:text-7xl font-extrabold leading-[1.05] mb-6 sm:mb-12 text-[#001733]">
                {article.title}
              </h1>

              <div className="flex flex-col sm:flex-row sm:items-center justify-between border-y border-gray-100 py-5 sm:py-8 mb-8 sm:mb-12 gap-5 sm:gap-8">
                <div className="flex items-center gap-5">
                  <img src={`https://i.pravatar.cc/100?u=${article.author}`} className="w-16 h-16 rounded-full object-cover grayscale" alt={article.author} />
                  <div>
                    <Link to={`/author/${encodeURIComponent(article.author)}`} className="block text-sm font-black uppercase tracking-widest text-black hover:text-[#e5002b] transition-colors">
                      By {article.author}
                    </Link>
                    <span className="text-xs text-gray-500 font-medium uppercase tracking-tighter">{article.date} • {article.readTime}</span>
                  </div>
                </div>

                <div className="flex items-center gap-2 sm:gap-3">
                  <span className="text-xs font-bold uppercase tracking-widest text-gray-400 mr-1 sm:mr-2">Share</span>
                  {/* X (Twitter) */}
                  <a href={`https://x.com/intent/tweet?url=${encodeURIComponent(window.location.href)}&text=${encodeURIComponent(article.title)}`} target="_blank" rel="noopener noreferrer" title="Share on X" className="w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center transition-all bg-black text-white hover:opacity-80">
                    <svg className="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                  </a>
                  {/* Facebook */}
                  <a href={`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(window.location.href)}`} target="_blank" rel="noopener noreferrer" title="Share on Facebook" className="w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center transition-all bg-[#1877F2] text-white hover:opacity-80">
                    <svg className="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                  </a>
                  {/* Instagram */}
                  <a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer" title="Share on Instagram" className="w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center transition-all text-white hover:opacity-80" style={{background: 'linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888)'}}>
                    <svg className="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                  </a>
                  {/* TikTok */}
                  <a href="https://www.tiktok.com/" target="_blank" rel="noopener noreferrer" title="Share on TikTok" className="w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center transition-all bg-black text-white hover:opacity-80">
                    <svg className="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 0010.86 4.48v-7.15a8.16 8.16 0 005.58 2.17v-3.46a4.85 4.85 0 01-1.99-4.55h1.99z"/></svg>
                  </a>
                </div>
              </div>

              <figure className="mb-12">
                <div className="aspect-video w-full overflow-hidden bg-gray-100 rounded-sm shadow-xl">
                  <img src={getImageUrl(article.image)} className="w-full h-full object-cover" alt={article.title} />
                </div>
                <figcaption className="mt-4 text-xs text-gray-400 font-medium italic border-l-2 border-gray-200 pl-5">
                  Photo Credit: The Bold East Africa Intelligence / Reuters.
                </figcaption>
              </figure>

              <div className="prose prose-lg md:prose-xl max-w-none text-gray-800 leading-relaxed space-y-6 sm:space-y-10 font-light tracking-wide">
                <p className="text-lg sm:text-2xl md:text-3xl font-serif italic text-gray-500 mb-12 leading-relaxed border-l-4 border-[#001733] pl-4 sm:pl-10">
                  {article.excerpt}
                </p>

                <div className="article-content text-base sm:text-lg md:text-xl leading-relaxed text-gray-800 font-light tracking-wide">
                  {article.content ? (
                    processContent(article.content).map((block: any, index: number) => {
                      // Inject "Also Read" after 2nd and 5th blocks
                      let injection = null;
                      if (index === 2 && relatedFromCategory.length > 0) {
                         injection = (
                           <div className="my-10 border-y border-gray-100 py-4 sm:py-6 bg-gray-50 px-4 sm:px-6">
                              <span className="text-xs font-black text-[#e5002b] uppercase tracking-widest block mb-2">Also Read</span>
                              <Link to={`/article/${createSlug(relatedFromCategory[0].title)}`} className="text-base sm:text-xl font-bold text-[#001733] hover:underline leading-tight block">
                                {relatedFromCategory[0].title}
                              </Link>
                           </div>
                         );
                      } else if (index === 5 && relatedFromCategory.length > 1) {
                         injection = (
                           <div className="my-10 border-y border-gray-100 py-4 sm:py-6 bg-gray-50 px-4 sm:px-6">
                              <span className="text-xs font-black text-[#e5002b] uppercase tracking-widest block mb-2">Related</span>
                              <Link to={`/article/${createSlug(relatedFromCategory[1].title)}`} className="text-base sm:text-xl font-bold text-[#001733] hover:underline leading-tight block">
                                {relatedFromCategory[1].title}
                              </Link>
                           </div>
                         );
                      }

                      let element;
                      if (block.type === 'image') {
                        element = (
                          <figure className="my-10">
                            <img src={block.src} alt={block.alt} className="w-full h-auto rounded-sm" />
                            {block.alt && <figcaption className="mt-2 text-sm text-gray-500 italic text-center">{block.alt}</figcaption>}
                          </figure>
                        );
                      } else if (block.type === 'quote') {
                         element = (
                           <blockquote className="my-10 pl-4 sm:pl-6 border-l-4 border-[#e5002b] italic text-lg sm:text-xl md:text-2xl text-gray-700 font-serif">
                             <div dangerouslySetInnerHTML={{ __html: block.content }} />
                           </blockquote>
                         );
                      } else {
                        element = <p className="mb-8" dangerouslySetInnerHTML={{ __html: block.content }} />;
                      }

                      return (
                        <React.Fragment key={index}>
                          {element}
                          {injection}
                        </React.Fragment>
                      );
                    })
                  ) : (
                    <div className="text-xl space-y-8 text-gray-500 italic">
                      <p>Full article content is not available yet.</p>
                    </div>
                  )}
                </div>
              </div>
            </article>

            <section className="mt-12 sm:mt-24">
              <div className="flex justify-between items-end border-b-2 border-black pb-4 mb-12">
                <h3 className="text-xl sm:text-2xl md:text-3xl font-bold uppercase tracking-wide sm:tracking-widest">You May Also Like</h3>
                <Link to="/" className="text-xs font-bold uppercase text-gray-400 hover:text-black shrink-0 ml-4">More News &rarr;</Link>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-12">
                {relatedFromCategory.map(a => (
                  <ArticleCard key={a.id} article={a} layout="grid" />
                ))}
              </div>
            </section>
          </div>

          <aside className="lg:col-span-4 flex flex-col gap-8 lg:gap-16">
            <div className="lg:sticky lg:top-28 space-y-8 lg:space-y-16">
              {article.category === 'Sports' && (
                <div className="mb-8">
                  <MatchFixturesWidget />
                </div>
              )}

              <div className="bg-gray-50 p-5 sm:p-10 rounded-sm">
                <h3 className="text-xs font-black uppercase tracking-[0.2em] text-[#001733] border-b border-gray-200 pb-3 mb-8">More in {article.category}</h3>
                <div className="space-y-8">
                  {relatedFromCategory.slice(0, 3).map(a => (
                    <div key={a.id} className="group border-b border-gray-100 last:border-0 pb-8 last:pb-0">
                      <Link to={`/article/${createSlug(a.title)}`}>
                        <span className="text-[10px] font-bold text-[#e5002b] uppercase tracking-widest mb-2 block">{a.date}</span>
                        <h4 className="text-xl font-bold leading-snug group-hover:text-[#001733] transition-colors">{a.title}</h4>
                      </Link>
                    </div>
                  ))}
                </div>
              </div>

              <AdSlot type="banner" />

              <div>
                <h3 className="text-xs font-black uppercase tracking-[0.2em] text-[#001733] border-b border-black pb-3 mb-8">Trending Now</h3>
                <div className="space-y-8">
                  {trendingArticles.map((a, i) => (
                    <div key={a.id} className="flex gap-6 group cursor-pointer">
                      <div className="text-5xl font-extrabold text-gray-100 group-hover:text-gray-200 transition-colors">0{i + 1}</div>
                      <div>
                        <Link to={`/article/${createSlug(a.title)}`}>
                          <h4 className="font-bold text-lg leading-tight mb-3 group-hover:text-[#e5002b] transition-colors">{a.title}</h4>
                          <span className="text-xs text-gray-400 font-bold uppercase tracking-widest">{a.category}</span>
                        </Link>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </aside>
        </div>
      </main>
    </div>
  );
};

export default ArticleDetailView;
