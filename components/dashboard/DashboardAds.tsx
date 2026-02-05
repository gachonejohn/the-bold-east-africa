import React, { useState } from 'react';
import { api, getImageUrl } from '../../services/api';
import { API_BASE_URL } from '../../services/config';

interface DashboardAdsProps {
  ads: any[];
  setAds: (ads: any[]) => void;
}

const DashboardAds: React.FC<DashboardAdsProps> = ({ ads, setAds }) => {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [formData, setFormData] = useState({
    name: '',
    company: '',
    type: 'Leaderboard',
    image: '',
    targetUrl: '',
    status: 'Active',
    startDate: '',
    endDate: '',
    price: '',
    invoice: ''
  });
  const [loading, setLoading] = useState(false);
  const [imageFile, setImageFile] = useState<File | null>(null);
  const [imagePreview, setImagePreview] = useState<string>('');
  const [uploadingImage, setUploadingImage] = useState(false);
  const fileInputRef = React.useRef<HTMLInputElement>(null);

  // Calculate total revenue dynamically
  const totalRevenue = ads.reduce((acc, ad) => {
    const priceStr = String(ad.price || '0').replace(/[^0-9.]/g, '');
    const price = parseFloat(priceStr);
    return acc + (isNaN(price) ? 0 : price);
  }, 0);

  // Handle file selection
  const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      // Validate file type
      if (!file.type.startsWith('image/')) {
        alert('Please select an image file');
        return;
      }
      // Validate file size (max 5MB)
      if (file.size > 15 * 1024 * 1024) {
        alert('Image size should be less than 5MB');
        return;
      }
      setImageFile(file);
      // Create preview
      const reader = new FileReader();
      reader.onloadend = () => {
        setImagePreview(reader.result as string);
      };
      reader.readAsDataURL(file);
    }
  };

  // Upload image to server
  const uploadImage = async (file: File): Promise<string> => {
    const formData = new FormData();
    formData.append('image', file);

    const response = await fetch(`${API_BASE_URL}/campaigns/upload-image`, {
      method: 'POST',
      body: formData,
    });

    if (!response.ok) {
      throw new Error('Failed to upload image');
    }

    const data = await response.json();
    return data.data?.path || data.path;
  };

  const handleOpenModal = (ad?: any) => {
    // Reset image state
    setImageFile(null);
    setImagePreview('');
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }

    if (ad) {
      setEditingId(ad.id);
      setFormData({
        name: ad.name,
        company: ad.company,
        type: ad.type,
        image: ad.image || '',
        targetUrl: ad.targetUrl || ad.target_url || '',
        status: ad.status,
        startDate: ad.startDate || '',
        endDate: ad.endDate || '',
        price: ad.price || '',
        invoice: ad.invoice || ''
      });
      // Set preview for existing image
      if (ad.image) {
        setImagePreview(ad.image);
      }
    } else {
      setEditingId(null);
      setFormData({
        name: '',
        company: '',
        type: 'Leaderboard',
        image: '',
        targetUrl: '',
        status: 'Active',
        startDate: '',
        endDate: '',
        price: '',
        invoice: ''
      });
    }
    setIsModalOpen(true);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      let submitData = { ...formData };

      // If there's a new image file, upload it first
      if (imageFile) {
        setUploadingImage(true);
        try {
          const imagePath = await uploadImage(imageFile);
          submitData.image = imagePath;
        } catch (uploadError) {
          console.error('Failed to upload image:', uploadError);
          alert('Failed to upload image. Please try again.');
          setLoading(false);
          setUploadingImage(false);
          return;
        }
        setUploadingImage(false);
      }

      if (editingId) {
        const res = await api.campaigns.update(editingId, submitData);
        setAds(ads.map(a => a.id === editingId ? res.data : a));
      } else {
        const res = await api.campaigns.create(submitData);
        setAds([...ads, res.data]);
      }
      setIsModalOpen(false);
      // Reset image state
      setImageFile(null);
      setImagePreview('');
    } catch (error) {
      console.error('Failed to save campaign', error);
      alert('Failed to save campaign. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!window.confirm('Are you sure you want to delete this campaign?')) return;
    try {
      await api.campaigns.delete(id);
      setAds(ads.filter(a => a.id !== id));
    } catch (error) {
      console.error('Failed to delete campaign', error);
    }
  };

  return (
    <div>
      <div className="flex justify-between items-center mb-8">
        <div className="flex gap-4">
           <div className="bg-white px-4 py-2 rounded-sm border border-gray-200 shadow-sm">
              <span className="block text-[10px] font-bold text-gray-400 uppercase tracking-widest">Active Campaigns</span>
              <span className="text-xl font-black text-[#001733]">{ads.filter(a => a.status === 'Active').length}</span>
           </div>
           <div className="bg-white px-4 py-2 rounded-sm border border-gray-200 shadow-sm">
              <span className="block text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Revenue</span>
              <span className="text-xl font-black text-[#001733]">${totalRevenue.toLocaleString()}</span>
           </div>
        </div>
        <button
          onClick={() => handleOpenModal()}
          className="bg-[#e5002b] text-white px-6 py-3 text-xs font-black uppercase tracking-widest hover:bg-[#001733] transition-colors shadow-lg"
        >
          + New Campaign
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {ads.map(ad => (
          <div key={ad.id} className="bg-white border border-gray-100 rounded-sm shadow-sm group hover:shadow-md transition-shadow">
            <div className="aspect-[3/1] bg-gray-100 relative overflow-hidden border-b border-gray-100">
              <img src={getImageUrl(ad.image)} alt={ad.name} className="w-full h-full object-cover" />
              <div className="absolute top-2 right-2">
                <span className={`px-2 py-1 text-[9px] font-black uppercase tracking-widest text-white ${ad.status === 'Active' ? 'bg-green-500' : 'bg-gray-400'}`}>
                  {ad.status}
                </span>
              </div>
            </div>
            <div className="p-5">
              <div className="flex justify-between items-start mb-2">
                <div>
                  <h4 className="font-bold text-[#001733] leading-tight">{ad.name}</h4>
                  <p className="text-xs text-gray-400 font-medium uppercase tracking-wide">{ad.company}</p>
                </div>
                <span className="text-[10px] font-bold bg-gray-100 text-gray-500 px-2 py-1 rounded uppercase">{ad.type}</span>
              </div>

              <div className="grid grid-cols-2 gap-4 my-4 py-4 border-y border-gray-50">
                 <div>
                    <span className="block text-[9px] text-gray-400 uppercase font-bold">Impressions</span>
                    <span className="font-bold text-[#001733]">{ad.impressions || 0}</span>
                 </div>
                 <div>
                    <span className="block text-[9px] text-gray-400 uppercase font-bold">Clicks</span>
                    <span className="font-bold text-[#001733]">{ad.clicks || 0}</span>
                 </div>
              </div>

              <div className="flex gap-2 mt-4">
                <button
                  onClick={() => handleOpenModal(ad)}
                  className="flex-1 py-2 border border-gray-200 text-[10px] font-bold uppercase tracking-widest text-gray-600 hover:bg-gray-50 hover:text-[#001733] transition-colors"
                >
                  Edit
                </button>
                <button
                  onClick={() => handleDelete(ad.id)}
                  className="px-3 py-2 border border-gray-200 text-gray-400 hover:bg-red-50 hover:text-red-600 hover:border-red-100 transition-colors"
                >
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Modal */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
          <div className="bg-white w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-sm shadow-2xl">
            <div className="p-6 border-b border-gray-100 flex justify-between items-center">
              <h3 className="text-lg font-black uppercase tracking-widest text-[#001733]">{editingId ? 'Edit Campaign' : 'New Campaign'}</h3>
              <button onClick={() => setIsModalOpen(false)} className="text-gray-400 hover:text-black">
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>
            <form onSubmit={handleSubmit} className="p-8 space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2">Campaign Name</label>
                  <input type="text" required value={formData.name} onChange={e => setFormData({...formData, name: e.target.value})} className="w-full border border-gray-200 p-3 text-sm focus:border-[#001733] outline-none" placeholder="e.g. Summer Sale 2024" />
                </div>
                <div>
                  <label className="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2">Client / Company</label>
                  <input type="text" required value={formData.company} onChange={e => setFormData({...formData, company: e.target.value})} className="w-full border border-gray-200 p-3 text-sm focus:border-[#001733] outline-none" placeholder="e.g. Nike" />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2">Ad Type</label>
                  <select value={formData.type} onChange={e => setFormData({...formData, type: e.target.value})} className="w-full border border-gray-200 p-3 text-sm focus:border-[#001733] outline-none bg-white">
                    <option value="Leaderboard">Leaderboard (728x90)</option>
                    <option value="Banner">Banner (320x50)</option>
                    <option value="MREC">MREC (300x250)</option>
                  </select>
                </div>
                <div>
                  <label className="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2">Status</label>
                  <select value={formData.status} onChange={e => setFormData({...formData, status: e.target.value})} className="w-full border border-gray-200 p-3 text-sm focus:border-[#001733] outline-none bg-white">
                    <option value="Active">Active</option>
                    <option value="Scheduled">Scheduled</option>
                    <option value="Ended">Ended</option>
                    <option value="Paused">Paused</option>
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2">Start Date</label>
                  <input type="date" value={formData.startDate} onChange={e => setFormData({...formData, startDate: e.target.value})} className="w-full border border-gray-200 p-3 text-sm focus:border-[#001733] outline-none" />
                </div>
                <div>
                  <label className="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2">End Date</label>
                  <input type="date" value={formData.endDate} onChange={e => setFormData({...formData, endDate: e.target.value})} className="w-full border border-gray-200 p-3 text-sm focus:border-[#001733] outline-none" />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2">Campaign Amount</label>
                  <input type="text" value={formData.price} onChange={e => setFormData({...formData, price: e.target.value})} className="w-full border border-gray-200 p-3 text-sm focus:border-[#001733] outline-none" placeholder="e.g. $5,000" />
                </div>
                <div>
                  <label className="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2">Invoice Number</label>
                  <input type="text" value={formData.invoice} onChange={e => setFormData({...formData, invoice: e.target.value})} className="w-full border border-gray-200 p-3 text-sm focus:border-[#001733] outline-none" placeholder="e.g. INV-2024-001" />
                </div>
              </div>

              <div>
                <label className="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2">Campaign Image</label>

                {/* File Upload Area */}
                <div
                  className={`border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-colors ${
                    imagePreview || formData.image ? 'border-green-300 bg-green-50' : 'border-gray-300 hover:border-[#001733] hover:bg-gray-50'
                  }`}
                  onClick={() => fileInputRef.current?.click()}
                >
                  <input
                    ref={fileInputRef}
                    type="file"
                    accept="image/*"
                    onChange={handleFileSelect}
                    className="hidden"
                  />

                  {(imagePreview || formData.image) ? (
                    <div className="space-y-3">
                      <img
                        src={imageFile ? imagePreview : getImageUrl(formData.image)}
                        alt="Preview"
                        className="max-h-32 object-contain mx-auto rounded"
                      />
                      <p className="text-xs text-gray-500">
                        {imageFile ? imageFile.name : 'Current image'}
                      </p>
                      <button
                        type="button"
                        onClick={(e) => {
                          e.stopPropagation();
                          setImageFile(null);
                          setImagePreview('');
                          setFormData({...formData, image: ''});
                          if (fileInputRef.current) fileInputRef.current.value = '';
                        }}
                        className="text-xs text-red-500 hover:text-red-700 font-medium"
                      >
                        Remove Image
                      </button>
                    </div>
                  ) : (
                    <div className="space-y-2">
                      <svg className="w-10 h-10 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      <p className="text-sm text-gray-600 font-medium">Click to upload image</p>
                      <p className="text-xs text-gray-400">PNG, JPG, GIF, WebP up to 5MB</p>
                    </div>
                  )}
                </div>

                {/* Recommended sizes */}
                <div className="mt-2 text-xs text-gray-400">
                  Recommended sizes: Leaderboard (728x90), Banner (320x50), MREC (300x250)
                </div>
              </div>

              <div>
                <label className="block text-xs font-black uppercase tracking-widest text-gray-500 mb-2">Target URL</label>
                <input type="url" value={formData.targetUrl} onChange={e => setFormData({...formData, targetUrl: e.target.value})} className="w-full border border-gray-200 p-3 text-sm focus:border-[#001733] outline-none" placeholder="https://..." />
              </div>

              <div className="pt-6 border-t border-gray-100 flex justify-end gap-4">
                <button type="button" onClick={() => setIsModalOpen(false)} className="px-6 py-3 text-xs font-bold uppercase tracking-widest text-gray-500 hover:text-black">Cancel</button>
                <button type="submit" disabled={loading} className="bg-[#001733] text-white px-8 py-3 text-xs font-black uppercase tracking-widest hover:bg-[#e5002b] transition-colors disabled:opacity-50">
                  {loading ? 'Saving...' : 'Save Campaign'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default DashboardAds;
