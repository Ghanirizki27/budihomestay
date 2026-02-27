import unittest



class TestTambah(unittest.TestCase):
    
    def test_tambah_positif(self):
        self.assertEqual(tambah(2, 3), 5)
    
    def test_tambah_negatif(self):
        self.assertEqual(tambah(-1, -1), -2)
    
    def test_tambah_campuran(self):
        self.assertEqual(tambah(5, -3), 2)

if __name__ == '__main__':
    unittest.main()